<?php

use ElasticNomad\Nomad;

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$validOperations = [
    'backup',
    'restore',
];
$operationsParams = [
    'backup' => [
        'index',
    ],
    'restore' => [
        'file_name',
    ],
];

$operation = $argv[1] ?? '';

if (!in_array($operation, $validOperations)) {
    echo 'Please, use a valid operation: ' . implode(', ', $validOperations);
    die;
}

$params = array_slice(
    $argv,
    2
);

if (
    isset($operationsParams[$operation]) &&
    count($params) < count($operationsParams[$operation])
) {
    echo 'Please, provide all the parameters: ' . implode(', ', $operationsParams[$operation]);
    die;
}

$options = [];
if (isset($operationsParams[$operation])) {
    foreach ($operationsParams[$operation] as $index => $paramName) {
        $options[$paramName] = $params[$index];
    }
}

$nomad = new Nomad();
$nomad->{$operation}($options);
