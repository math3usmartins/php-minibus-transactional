<?php

namespace MiniBus\Transactional\Test\Middleware;

use Closure;
use Exception;
use MiniBus\Envelope;
use MiniBus\Envelope\BasicEnvelope;
use MiniBus\Envelope\Stamp\StampCollection;
use MiniBus\Handler\HandlerCollection;
use MiniBus\Handler\HandlerMiddleware;
use MiniBus\Handler\HandlerStamp;
use MiniBus\Handler\Locator\SubjectLocator;
use MiniBus\Middleware;
use MiniBus\Test\StubMessage;
use MiniBus\Test\Transport\Serializer\StubNormalizer;
use MiniBus\Test\Transport\Unserializer\Denormalizer\StubDenormalizer;
use MiniBus\Test\Transport\Unserializer\Denormalizer\StubDenormalizerLocator;
use MiniBus\Transactional\Database\Doctrine\DoctrineConnection;
use MiniBus\Transactional\Database\Doctrine\DoctrineRepository;
use MiniBus\Transactional\Middleware\Stamp\TransactionalMiddlewareStamp;
use MiniBus\Transactional\Middleware\TransactionalMiddleware;
use MiniBus\Transactional\Sender\Stamp\TransactionalSenderStamp;
use MiniBus\Transactional\Sender\TransactionalSender;
use MiniBus\Transactional\Test\Database\Doctrine\TestDoctrineSchemaManager;
use MiniBus\Transactional\Test\Handler\FailingHandler;
use MiniBus\Transactional\Test\StubClock;
use MiniBus\Transport\Handler\TransportHandler;
use MiniBus\Transport\Sender\SenderStamp;
use MiniBus\Transport\Serializer\JsonSerializer;
use MiniBus\Transport\Unserializer\JsonUnserializer;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \MiniBus\Transactional\Database\Doctrine\DoctrineConnection
 * @covers \MiniBus\Transactional\Database\Doctrine\DoctrineSchemaManager
 * @covers \MiniBus\Transactional\Middleware\TransactionalMiddleware
 */
final class TransactionalMiddlewareTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        TestDoctrineSchemaManager::setup();
    }

    /**
     * @dataProvider scenarios
     */
    public function testItMustHandleGivenEnvelope(
        Envelope $envelope,
        TransactionalMiddleware $transactionalMiddleware,
        Middleware $nextMiddleware,
        Closure $assertionCallback
    ) {
        try {
            $actualEnvelope = $transactionalMiddleware->handle($envelope, $nextMiddleware);
            $assertionCallback($actualEnvelope);
        } catch (Exception $exception) {
            $assertionCallback($exception);
        }
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

        $transactionalMiddleware = new TransactionalMiddleware(
            new DoctrineConnection($connection)
        );

        yield 'database transaction is committed with expected changes' => [
            'envelope' => $envelope,
            'transactional middleware' => $transactionalMiddleware,
            'next middleware' => new HandlerMiddleware(
                new SubjectLocator([
                    $subject => new HandlerCollection([
                        new TransportHandler(new TransactionalSender($repository)),
                    ]),
                ])
            ),
            'assertion callback' => function (Envelope $actualEnvelope) use ($envelope, $normalized, $connection, $tableName) {
                $expectedEnvelope = $envelope
                    ->withStamp(new TransactionalMiddlewareStamp())
                    ->withStamp(new TransactionalSenderStamp())
                    ->withStamp(new HandlerStamp())
                    ->withStamp(new SenderStamp());

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

        $exception = new Exception('something went wrong');

        yield 'database transaction is NOT committed because of exception' => [
            'envelope' => $envelope,
            'transactional middleware' => $transactionalMiddleware,
            'next middleware' => new HandlerMiddleware(
                new SubjectLocator([
                    $subject => new HandlerCollection([
                        new TransportHandler(new TransactionalSender($repository)),
                        new FailingHandler($exception),
                    ]),
                ])
            ),
            'assertion callback' => function (Exception $actualException) use ($exception, $connection, $tableName) {
                static::assertEquals($exception, $actualException);

                $actualRowCount = $connection->createQueryBuilder()
                    ->select('t.*')
                    ->from($tableName, 't')
                    ->execute()
                    ->rowCount();

                static::assertEquals(0, $actualRowCount);
            },
        ];
    }
}
