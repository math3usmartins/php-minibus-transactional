#!/usr/local/bin/php
<?php

function tryToConnect()
{
    $dsn = strtr(
        'mysql:host=DB_HOST;port=DB_PORT;dbname=DB_NAME',
        [
            'DB_HOST' => getenv('DB_HOST'),
            'DB_PORT' => getenv('DB_PORT'),
            'DB_NAME' => getenv('DB_NAME'),
        ]
    );

    echo "\n" . "Checking database connection at ${dsn}" . "\n";

    try {
        $pdo = new \PDO($dsn, getenv('DB_USER'), getenv('DB_PWD'));
        $result = $pdo->query('SELECT 1 AS res')->fetch(\PDO::FETCH_ASSOC);

        return is_array($result) && isset($result['res']) && '1' === $result['res'];
    } catch (\Exception $e) {
        echo "\n" . 'Failed with following error:'
            . "\n" . $e->getMessage() . "\n";

        return false;
    }
}

for ($n=1; $n<=getenv('TIMEOUT'); $n++) {
    $success = tryToConnect();

    if ($success) {
        echo "\n" . 'SUCCESS' . "\n";
        exit(0);
    }

    sleep(1);
}

exit(1);
