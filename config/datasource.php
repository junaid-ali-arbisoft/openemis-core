<?php
return [
        'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'teachtheworldserver.mysql.database.azure.com',
            //'port' => 'nonstandard_port_number',
            'username' => 'adnan',
            'password' => 'Admin_12345',
            'database' => 'core',
            'encoding' => 'utf8',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => true,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ]
    ]
];
