<?php

namespace MiniBus\Transactional\Database\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use MiniBus\Transactional\SchemaManager;

final class DoctrineSchemaManager implements SchemaManager
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
     * @param string $tableName
     *
     * @throws DBALException
     */
    public function setUp($tableName)
    {
        $schemaManager = $this->connection->getSchemaManager();

        $notNull = ['notnull' => true];
        $unsigned = ['unsigned' => true];

        $columns = [
            new Column(
                'id',
                Type::getType(TYPE::SMALLINT),
                ['autoincrement' => true]
            ),
            new Column(
                'created_at',
                Type::getType(TYPE::INTEGER),
                $notNull + $unsigned
            ),
            new Column(
                'attempts',
                Type::getType(TYPE::INTEGER),
                $notNull + $unsigned
            ),
            new Column(
                'subject',
                Type::getType(TYPE::STRING),
                $notNull + ['length' => 255]
            ),
            new Column(
                'envelope',
                Type::getType(TYPE::TEXT),
                $notNull
            ),
        ];

        $indexes = [
            new Index('PRIMARY', ['id'], true, true),
            new Index('idx_subject', ['subject']),
        ];

        $table = new Table($tableName, $columns, $indexes);

        $schemaManager->createTable($table);
    }
}
