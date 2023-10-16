<?php

namespace MiniBus\Transactional\Database\Doctrine;

use Closure;
use Doctrine\DBAL\Connection;
use Exception;
use MiniBus\Transactional\Database\DatabaseConnection;

final class DoctrineConnection implements DatabaseConnection
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    public function transactional(Closure $closure)
    {
        $this->connection->transactional($closure);
    }
}
