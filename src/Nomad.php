<?php

namespace ElasticNomad;

use ElasticNomad\Backup;
use ElasticNomad\Restore;

class Nomad
{
    /**
     * Execute backup.
     *
     * @param array $options
     * @return void
     */
    public function backup(
        array $options
    ): void {
        $backup = $this->newBackup();
        $backup->execute(
            [
                'elasticsearch' => [
                    'size' => getenv('BACKUP_ELASTICSEARCH_SIZE') ?? 25,
                    'index' => $options['index'] ?? '',
                ],
                's3' => [
                    'enabled' => getenv('BACKUP_S3_ENABLED') ?? 0,
                    'bucket' => getenv('BACKUP_S3_BUCKET') ?? '',
                    'folder' => getenv('BACKUP_S3_FOLDER') ?? '',
                ],
                'file' => [
                    'total_items' => getenv('BACKUP_FILE_TOTAL_ITEMS') ?? 100,
                    'date' => date('Ymd'),
                    'time' => date('His'),
                ],
            ]
        );
    }

    /**
     * Execute restore.
     *
     * @param array $options
     * @return void
     */
    public function restore(
        array $options
    ): void {
        $restore = $this->newRestore();
        $restore->execute([
            'file' => [
                'name' => $options['file_name'] ?? '',
            ],
        ]);
    }

    /**
     * Get new Backup object.
     *
     * @return Backup
     */
    public function newBackup(): Backup
    {
        return new Backup();
    }

    /**
     * Get new Restore object.
     *
     * @return Restore
     */
    public function newRestore(): Restore
    {
        return new Restore();
    }
}
