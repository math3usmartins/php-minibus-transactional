<?php

namespace MiniBus\Transactional\Test\Database\Doctrine;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use MiniBus\Transactional\Database\Doctrine\DoctrineSchemaManager;

final class TestDoctrineSchemaManager
{
    /**
     * @return Connection
     */
    public static function setup()
    {
        $connection = self::connect();
        $connection->getSchemaManager()->dropAndCreateDatabase(self::dbName());

        // restart connection
        $connection->close();
        $connection = self::connect();

        (new DoctrineSchemaManager($connection))->setUp(self::tableName());

        return $connection;
    }

    public static function tableName()
    {
        return (string) getenv('DB_TABLE');
    }

    public static function dbName()
    {
        return (string) getenv('DB_NAME');
    }

    private static function connect()
    {
        $config = new Configuration();

        $params = [
            'dbname' => self::dbName(),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PWD'),
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
            'driver' => 'pdo_mysql',
        ];

        return DriverManager::getConnection($params, $config);
    }
}
