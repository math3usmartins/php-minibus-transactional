<?php

namespace MiniBus\Transactional\Database\Doctrine;

use Doctrine\DBAL\Connection;
use Exception;
use MiniBus\Envelope;
use MiniBus\Envelope\EnvelopeCollection;
use MiniBus\Transactional\Clock;
use MiniBus\Transactional\Database\Exception\FailedToInsertEnvelope;
use MiniBus\Transactional\Database\Exception\FailedToUpdateEnvelope;
use MiniBus\Transactional\EnvelopeRepository;
use MiniBus\Transactional\Receiver\Stamp\TransactionalReceiverStamp;
use MiniBus\Transport\Receiver\RetryStamp;
use MiniBus\Transport\Serializer;
use MiniBus\Transport\Unserializer;
use PDO;

final class DoctrineRepository implements EnvelopeRepository
{
    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Unserializer
     */
    private $unserializer;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @param string $tableName
     */
    public function __construct(
        Clock $clock,
        Connection $connection,
        Serializer $serializer,
        Unserializer $unserializer,
        $tableName
    ) {
        $this->clock = $clock;
        $this->connection = $connection;
        $this->serializer = $serializer;
        $this->unserializer = $unserializer;
        $this->tableName = $tableName;
    }

    /**
     * @throws Exception
     * @throws FailedToInsertEnvelope
     */
    public function insert(Envelope $envelope)
    {
        $numAttempts = $envelope->stamps()->all(RetryStamp::NAME)->count();

        $queryBuilder = $this->connection->createQueryBuilder();
        $affectedRows = $queryBuilder->insert($this->tableName)
            ->values([
                'created_at' => ':created_at',
                'subject' => ':subject',
                'envelope' => ':envelope',
                'attempts' => ':attempts',
            ])
            ->setParameters([
                'created_at' => $this->clock->timestamp(),
                'subject' => $envelope->message()->subject(),
                'envelope' => $this->serializer->serialize($envelope),
                'attempts' => $numAttempts,
            ])
            ->execute();

        if (0 === $affectedRows) {
            throw FailedToInsertEnvelope::fromEnvelope($envelope);
        }
    }

    /**
     * @param int $id
     */
    public function update($id, Envelope $envelope)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $affectedRows = $queryBuilder->update($this->tableName)
            ->values([
                'created_at' => ':created_at',
                'subject' => ':subject',
                'envelope' => ':envelope',
            ])
            ->where('id = :id')
            ->setParameters([
                'id' => $id,
                'created_at' => $this->clock->timestamp(),
                'subject' => $envelope->message()->subject(),
                'envelope' => $this->serializer->serialize($envelope),
            ])
            ->execute();

        if (0 === $affectedRows) {
            throw FailedToUpdateEnvelope::fromEnvelope($envelope);
        }
    }

    /**
     * @param int $limit
     *
     * @return EnvelopeCollection
     */
    public function find($limit)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $rows = $queryBuilder->select('t.*')
            ->from($this->tableName, 't')
            ->setMaxResults($limit)
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->mapResults($rows);
    }

    /**
     * @param int[] $idValues
     */
    public function deleteById($idValues)
    {
        $params = [
            'id_values' => $idValues,
        ];

        $paramTypes = [
            'id_values' => Connection::PARAM_INT_ARRAY,
        ];

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->delete($this->tableName, 't')
            ->where(
                $queryBuilder->expr()->in('t.id', 'id_values')
            )
            ->setParameters($params, $paramTypes)
            ->execute();
    }

    /**
     * @return EnvelopeCollection
     */
    private function mapResults(array $rows)
    {
        $envelopes = array_map(
            function (array $row) {
                return $this->unserializer
                    ->unserialize($row['envelope'])
                    ->withStamp(new TransactionalReceiverStamp($row['id']));
            },
            $rows
        );

        return new EnvelopeCollection($envelopes);
    }
}
