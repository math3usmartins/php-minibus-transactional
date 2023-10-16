<?php

namespace MiniBus\Transactional\Middleware;

use Exception;
use MiniBus\Envelope;
use MiniBus\Middleware;
use MiniBus\Transactional\Database\DatabaseConnection;
use MiniBus\Transactional\Middleware\Stamp\TransactionalMiddlewareStamp;

final class TransactionalMiddleware implements Middleware
{
    /**
     * @var DatabaseConnection
     */
    private $connection;

    public function __construct(
        DatabaseConnection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    public function handle(Envelope $envelope, Middleware $next = null)
    {
        $transactionalEnvelope = $envelope->withStamp(new TransactionalMiddlewareStamp());

        $this->connection->transactional(
            function () use (&$transactionalEnvelope, $next) {
                $transactionalEnvelope = $next->handle($transactionalEnvelope);
            }
        );

        return $transactionalEnvelope;
    }
}
