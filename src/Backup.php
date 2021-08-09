<?php

namespace ElasticNomad;

use ElasticNomad\Helpers\Elasticsearch;
use ElasticNomad\Helpers\Log;
use ElasticNomad\Helpers\S3;
use Exception;
use Ulid\Ulid;

class Backup
{
    private $options = [
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
            'date' => '',
            'time' => '',
        ],
    ];

    private $currentFile = [];
    private $log;
    private $elasticsearch;
    private $s3;

    /**
     * Execute Backup.
     *
     * @param array $options
     * @return void
     */
    public function execute(
        array $options
    ): void {
        $this->setOptions($options);

        $this->log = $this->newLog();
        $this->elasticsearch = $this->newElasticsearch();
        $this->s3 = $this->newS3();

        $this->log->logStartTime();
        $this->log->show('Starting Backup');

        try {
            $query = $this->loadSearchQuery();
            $response = $this->elasticsearch->search(
                $this->options['elasticsearch']['index'],
                $query,
                $this->options['elasticsearch']['size']
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

            $this->log->show('Backup finished');
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
            $this->currentFile['size'] >= $this->options['file']['total_items']
        ) {
            $this->currentFile = $this->createFile();
            $indexLine = [
                'index' => $this->options['elasticsearch']['index'],
            ];
            file_put_contents(
                $this->currentFile['path'],
                json_encode($indexLine) . "\n"
            );
        }

        $content = [
            '_id' => $hit['_id'] ?? '',
            '_source' => $hit['_source'] ?? [],
        ];
        $content = json_encode($content) . "\n";

        file_put_contents(
            $this->currentFile['path'],
            $content,
            FILE_APPEND
        );
        file_put_contents(
            'logs/last-backup.log',
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

        $name = $this->options['elasticsearch']['index'] .
            '_' . $this->options['file']['date'] .
            '_' . $this->options['file']['time'] .
            '_' . $ulid .
            '.txt';
        $path = 'storage/backup/' . $name;

        $this->log->show("Creating new file: '" . $name . "'");

        return [
            'name' => $name,
            'path' => $path,
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
        if (!$this->options['s3']['enabled']) {
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
            $key = $this->options['s3']['folder'] .
                '/' .
                $this->options['elasticsearch']['index'] .
                '/' .
                $fileName;

            $this->s3->uploadFile(
                $this->options['s3']['bucket'],
                $key,
                $path . $fileName
            );
        }

        return true;
    }

    /**
     * Set options.
     *
     * @param array $options
     * @return void
     */
    public function setOptions(
        array $options
    ): void {
        $this->options = array_merge(
            $this->options,
            $options
        );
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
