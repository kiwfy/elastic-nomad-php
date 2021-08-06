<?php

namespace ElasticNomad;

use ElasticNomad\Backup;
use ElasticNomad\Restore;

class Nomad
{
    private $operationsMethods = [
        'backup' => 'newBackup',
        'restore' => 'newRestore',
    ];

    public function execute(
        string $operation
    ) {
        $method = $this->operationsMethods[$operation];
        $class = $this->$method();
        $class->execute();
    }

    public function newBackup()
    {
        return new Backup([
            'elasticsearch' => [
                'index' => getenv('BACKUP_ELASTICSEARCH_INDEX') ?? '',
                'size' => getenv('BACKUP_ELASTICSEARCH_SIZE') ?? 25,
            ],
            's3' => [
                'enabled' => getenv('BACKUP_S3_ENABLED') ?? 0,
                'bucket' => getenv('BACKUP_S3_BUCKET') ?? '',
                'folder' => getenv('BACKUP_S3_FOLDER') ?? '',
            ],
            'file' => [
                'total_items' => getenv('BACKUP_FILE_TOTAL_ITEMS') ?? 100,
            ],
        ]);
    }

    public function newRestore()
    {
        return new Restore([
            's3' => [
                'enabled' => getenv('RESTORE_S3_ENABLED') ?? 0,
                'bucket' => getenv('RESTORE_S3_BUCKET') ?? '',
                'files' => getenv('RESTORE_S3_FILES') ?? '',
            ],
        ]);
    }
}
