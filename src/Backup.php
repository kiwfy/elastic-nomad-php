<?php

namespace ElasticNomad;

use ElasticNomad\Helpers\Elasticsearch;
use ElasticNomad\Helpers\Log;
use ElasticNomad\Helpers\S3;
use Exception;
use Ulid\Ulid;

class Backup
{
    private $settings = [
        'elasticsearch' => [
            'index' => '',
            'size' => 25,
        ],
        's3' => [
            'enabled' => 0,
            'bucket' => '',
            'folder' => '',
        ],
        'file' => [
            'total_items' => 100,
        ],
    ];

    private $currentFile = [];

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
     * Execute Backup.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->log = $this->newLog();
        $this->elasticsearch = $this->newElasticsearch();
        $this->s3 = $this->newS3();

        $this->log->logStartTime();
        $this->log->show('Starting Backup');

        try {
            $query = $this->loadSearchQuery();
            $response = $this->elasticsearch->search(
                $this->settings['elasticsearch']['index'],
                $query,
                $this->settings['elasticsearch']['size']
            );

            while (
                isset($response['hits']['hits']) &&
                count($response['hits']['hits']) > 0
            ) {
                $hits = $response['hits']['hits'] ?? [];
                $this->handleHits(
                    $hits
                );

                $scrollId = $response['_scroll_id'] ?? null;
                $response = $this->elasticsearch->scroll(
                    $scrollId
                );
            }

            $this->uploadFiles();

            $this->log->showDuration();
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    /**
     * Load backup Elasticsearch query.
     *
     * @return array
     */
    public function loadSearchQuery(): array
    {
        $query = file_get_contents(
            'query.json'
        );
        $query = json_decode(
            $query,
            true
        );

        return $query;
    }

    /**
     * Handle Elasticsearch response hits.
     *
     * @param array $hits
     * @return bool
     */
    public function handleHits(
        array $hits
    ): bool {
        if (empty($hits)) {
            return false;
        }

        foreach ($hits as $hit) {
            $this->saveHit($hit);
        }

        return true;
    }

    /**
     * Save hit into file.
     *
     * @param array $hit
     * @return bool
     */
    public function saveHit(
        array $hit
    ): bool {
        if (
            empty($this->currentFile) ||
            $this->currentFile['size'] >= $this->settings['file']['total_items']
        ) {
            $this->currentFile = $this->createFile();
        }

        $content = json_encode($hit) . "\n";

        file_put_contents(
            $this->currentFile['path'],
            $content,
            FILE_APPEND
        );
        file_put_contents(
            'logs/last-backup.txt',
            $content
        );
        $this->currentFile['size'] ++;

        return true;
    }

    /**
     * Create new backup file.
     *
     * @return array
     */
    public function createFile(): array
    {
        $ulid = $this->newUlid()
            ->generate();

        $this->log->show("Creating new file: '" . $ulid . ".txt'");

        return [
            'name' => $ulid . '.txt',
            'path' => 'storage/backup/' . $ulid . '.txt',
            'size' => 0,
        ];
    }

    /**
     * Upload backup files.
     *
     * @return bool
     */
    public function uploadFiles(): bool
    {
        if (!$this->settings['s3']['enabled']) {
            return false;
        }

        $fileNames = scandir('storage/backup');
        $fileNames = array_slice(
            $fileNames,
            3
        );
        $path = 'storage/backup/';
        $totalFiles = count($fileNames);

        $this->log->show("Uploading $totalFiles files");

        foreach ($fileNames as $fileName) {
            $key = $this->settings['s3']['folder'] .
                '/' .
                $fileName;

            $this->s3->uploadFile(
                $this->settings['s3']['bucket'],
                $key,
                $path . $fileName
            );
        }

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
