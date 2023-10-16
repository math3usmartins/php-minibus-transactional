<?php

namespace MiniBus\Transactional\Test\Sender;

use Closure;
use MiniBus\Envelope;
use MiniBus\Envelope\BasicEnvelope;
use MiniBus\Envelope\Stamp\StampCollection;
use MiniBus\Test\StubMessage;
use MiniBus\Test\Transport\Serializer\StubNormalizer;
use MiniBus\Test\Transport\Unserializer\Denormalizer\StubDenormalizer;
use MiniBus\Test\Transport\Unserializer\Denormalizer\StubDenormalizerLocator;
use MiniBus\Transactional\Database\Doctrine\DoctrineRepository;
use MiniBus\Transactional\Sender\Stamp\TransactionalSenderStamp;
use MiniBus\Transactional\Sender\TransactionalSender;
use MiniBus\Transactional\Test\Database\Doctrine\TestDoctrineSchemaManager;
use MiniBus\Transactional\Test\StubClock;
use MiniBus\Transport\Serializer\JsonSerializer;
use MiniBus\Transport\Unserializer\JsonUnserializer;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \MiniBus\Transactional\Database\Doctrine\DoctrineConnection
 * @covers \MiniBus\Transactional\Database\Doctrine\DoctrineSchemaManager
 * @covers \MiniBus\Transactional\Sender\Stamp\TransactionalSenderStamp
 * @covers \MiniBus\Transactional\Sender\TransactionalSender
 */
final class TransactionalSenderTest extends TestCase
{
    /**
     * @dataProvider scenarios
     */
    public function testExecute(
        Envelope $envelope,
        TransactionalSender $sender,
        Closure $assertionCallback
    ) {
        $actual = $sender->send($envelope);
        $assertionCallback($actual);
    }

    public function scenarios()
    {
        $connection = TestDoctrineSchemaManager::setup();
        $tableName = TestDoctrineSchemaManager::tableName();

        $message = new StubMessage(
            $subject = 'some-subject',
            ['header' => 'x'],
            ['some-field' => 'some-value']
        );

        $envelope = new BasicEnvelope($message, new StampCollection([]));

        $normalized = [
            'stamps' => [],
            'message' => [
                'subject' => $subject,
                'headers' => [
                    'header' => 'x',
                ],
                'body' => [
                    'some-field' => 'some-value',
                ],
            ],
        ];

        $serializer = new JsonSerializer(
            new StubNormalizer($normalized)
        );

        $unserializer = new JsonUnserializer(
            new StubDenormalizerLocator(
                new StubDenormalizer($envelope)
            )
        );

        $repository = new DoctrineRepository(
            new StubClock(123456789),
            $connection,
            $serializer,
            $unserializer,
            $tableName
        );

        yield 'database transaction is committed with expected changes' => [
            'envelope' => $envelope,
            'sender' => new TransactionalSender($repository),
            'assertion callback' => function (Envelope $actualEnvelope) use ($envelope, $normalized, $connection, $tableName) {
                $expectedEnvelope = $envelope->withStamp(new TransactionalSenderStamp());

                static::assertEquals($expectedEnvelope, $actualEnvelope);

                $actualRow = $connection->createQueryBuilder()
                    ->select('t.*')
                    ->from($tableName, 't')
                    ->execute()
                    ->fetch(PDO::FETCH_ASSOC);

                static::assertEquals(123456789, $actualRow['created_at']);
                static::assertJson($actualRow['envelope']);

                $actualNormalizedEnvelope = json_decode($actualRow['envelope'], true);
                static::assertEquals($normalized, $actualNormalizedEnvelope);
            },
        ];
    }
}
