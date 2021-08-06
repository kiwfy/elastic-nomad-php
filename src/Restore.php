<?php

namespace ElasticNomad;

use ElasticNomad\Helpers\Elasticsearch;
use ElasticNomad\Helpers\Log;
use ElasticNomad\Helpers\S3;
use Exception;
use Ulid\Ulid;

class Restore
{
    private $settings = [
        's3' => [
            'enabled' => 0,
            'bucket' => '',
            'files' => '',
        ],
    ];

    private $log;
    private $elasticsearch;
    private $s3;

    public function __construct(
        array $settings
    ) {
        $this->settings = array_merge(
            $this->settings,
            $settings
        );
    }

    /**
     * Execute Restore.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->log = $this->newLog();
        $this->elasticsearch = $this->newElasticsearch();
        $this->s3 = $this->newS3();

        $this->log->logStartTime();
        $this->log->show('Starting Restore');

        try {
            $fileNames = $this->listFilesToRestore();

            foreach ($fileNames as $fileName) {
                $this->indexFileItems(
                    $fileName
                );
            }

            $this->log->showDuration();
        } catch (Exception $error) {
            print_r($error->getMessage());
        }
    }

    /**
     * Download files to restore from S3.
     *
     * @return bool
     */
    public function downloadFilesToRestore(): bool
    {
        if (!$this->settings['s3']['enabled']) {
            return false;
        }

        $keys = explode(
            ',',
            $this->settings['s3']['files']
        );
        $totalKeys = count($keys);

        $this->log->show("Downloading $totalKeys files from S3");

        foreach ($keys as $key) {
            $fileNameParts = explode(
                '/',
                $key
            );
            $fileName = end($fileNameParts);

            $this->s3->download(
                $this->settings['s3']['bucket'],
                $key,
                'storage/restore/' . $fileName
            );
        }

        return true;
    }

    /**
     * List files to restore.
     *
     * @return array
     */
    public function listFilesToRestore(): array
    {
        $files = scandir(
            'storage/restore'
        );
        $files = array_slice(
            $files,
            2
        );

        return $files;
    }

    /**
     * Index file items into Elasticsearch.
     *
     * @param string $fileName
     * @return bool
     */
    public function indexFileItems(
        string $fileName
    ): bool {
        $handle = fopen(
            'storage/restore/' . $fileName,
            'r'
        );

        if (empty($handle)) {
            return false;
        }

        while (($line = fgets($handle)) !== false) {
            $item = json_encode(
                trim($line),
                true
            );

            $this->elasticsearch->index(
                $item['_index'],
                $item['_id'],
                $item
            );
        }

        fclose($handle);

        return true;
    }

    /**
     * Get new Elasticsearch object.
     *
     * @return Elasticsearch
     */
    public function newElasticsearch(): Elasticsearch
    {
        return new Elasticsearch();
    }

    /**
     * Get new Log object.
     *
     * @return Log
     */
    public function newLog(): Log
    {
        return new Log();
    }

    /**
     * Get new S3 object.
     *
     * @return S3
     */
    public function newS3(): S3
    {
        return new S3();
    }

    /**
     * Get new Ulid object.
     *
     * @return Ulid
     */
    public function newUlid(): Ulid
    {
        return new Ulid();
    }
}
