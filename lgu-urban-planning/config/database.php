<?php
/**
 * Database Configuration
 */

return [
    'host' => 'localhost',
    'dbname' => 'upad_lgu_urban_planning',
    'username' => 'upad_lgu_urban_planning',
    'password' => 'lgu_urban_planning',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

