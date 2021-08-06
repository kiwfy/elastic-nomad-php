<?php

use ElasticNomad\Nomad;

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$validOperations = [
    'backup',
    'restore',
];
$operation = $argv[1] ?? '';

if (!in_array($operation, $validOperations)) {
    echo 'Please, use a valid operation: ' . implode(', ', $validOperations);
    die;
}

$nomad = new Nomad();
$nomad->execute($operation);
