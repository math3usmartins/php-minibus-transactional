<?php

namespace MiniBus\Transactional\Test\Database\Doctrine;

use Closure;
use Doctrine\DBAL\Connection;
use MiniBus\Envelope;
use MiniBus\Envelope\BasicEnvelopeFactory;
use MiniBus\Envelope\Stamp\StampCollection;
use MiniBus\Test\StubMessage;
use MiniBus\Test\Transport\Serializer\StubNormalizer;
use MiniBus\Test\Transport\Unserializer\Denormalizer\StubDenormalizer;
use MiniBus\Test\Transport\Unserializer\Denormalizer\StubDenormalizerLocator;
use MiniBus\Transactional\Database\Doctrine\DoctrineRepository;
use MiniBus\Transactional\Test\StubClock;
use MiniBus\Transport\Serializer\JsonSerializer;
use MiniBus\Transport\Unserializer\JsonUnserializer;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \MiniBus\Transactional\Database\Doctrine\DoctrineRepository
 */
final class DoctrineRepositoryTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        parent::setUp();

        $this->connection = TestDoctrineSchemaManager::setup();
        $this->connection->exec(sprintf('TRUNCATE `%s`', TestDoctrineSchemaManager::tableName()));
    }

    /**
     * @dataProvider scenariosToInsert
     */
    public function testInsert(
        Envelope $envelope,
        Closure $repositoryFactory,
        array $expectedRow
    ) {
        $tableName = TestDoctrineSchemaManager::tableName();

        /** @var DoctrineRepository $repository */
        $repository = $repositoryFactory($this->connection);
        $repository->insert($envelope);

        $actualRow = $this->connection->createQueryBuilder()
            ->select('t.*')
            ->from($tableName, 't')
            ->execute()
            ->fetch(PDO::FETCH_ASSOC);

        static::assertEquals($expectedRow['subject'], $actualRow['subject']);
        static::assertEquals($expectedRow['created_at'], $actualRow['created_at']);

        static::assertJson($actualRow['envelope']);
        static::assertEquals($expectedRow['envelope'], json_decode($actualRow['envelope'], true));
    }

    public function scenariosToInsert()
    {
        $clock = new StubClock(123456789);
        $subject = 'some-subject';
        $headers = ['header' => 'x'];
        $body = ['body' => 'z'];

        $message = new StubMessage($subject, $headers, $body);

        $envelopeFactory = new BasicEnvelopeFactory();
        $envelope = $envelopeFactory->create($message, new StampCollection([]));

        $normalizedMessage = [
            'headers' => $headers + ['subject' => $subject],
            'body' => $body,
        ];

        $serializer = new JsonSerializer(
            new StubNormalizer([
                'stamps' => [],
                'message' => $normalizedMessage,
            ])
        );

        $unserializer = new JsonUnserializer(
            new StubDenormalizerLocator(
                new StubDenormalizer($envelope)
            )
        );

        yield 'envelope must be inserted with given data' => [
            'envelope' => $envelope,
            'repository factory' => function (Connection $connection) use ($clock, $serializer, $unserializer) {
                return new DoctrineRepository(
                    $clock,
                    $connection,
                    $serializer,
                    $unserializer,
                    TestDoctrineSchemaManager::tableName()
                );
            },
            'expected row' => [
                'created_at' => 123456789,
                'subject' => $subject,
                'envelope' => [
                    'stamps' => [],
                    'message' => $normalizedMessage,
                ],
            ],
        ];
    }
}
