<?php

namespace ElasticNomad;

use ElasticNomad\Helpers\Elasticsearch;
use ElasticNomad\Helpers\Log;
use ElasticNomad\Helpers\S3;
use Exception;
use Ulid\Ulid;

class Restore
{
    private $options = [
        'file' => [
            'name' => '',
        ],
    ];

    private $log;
    private $elasticsearch;
    private $errorsCount = 0;

    /**
     * Execute Restore.
     *
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
        $this->log->show('Starting Restore');

        try {
            file_put_contents(
                'logs/errors.log',
                ''
            );

            $filePath = 'storage/restore/' . $this->options['file']['name'];
            $handle = fopen(
                $filePath,
                'r'
            );

            if (empty($handle)) {
                return;
            }

            $index = fgets($handle);
            $index = json_decode(
                $index,
                true
            );
            $indexName = $index['index'] ?? '';

            while (($row = fgets($handle)) !== false) {
                $this->indexItem(
                    $indexName,
                    $row
                );
            }

            fclose($handle);

            if ($this->errorsCount) {
                $this->log->show(
                    'Some documents could not be indexed because they already exist ' .
                    'within the specified index. You can check these items in the file ' .
                    '"logs/errors.log"'
                );
            }

            $this->log->show('Restore finished');
            $this->log->showDuration();
        } catch (Exception $error) {
            print_r($error->getMessage());
        }
    }

    /**
     * Index item into Elasticsearch.
     *
     * @param string $indexName
     * @param string $row
     * @return bool
     */
    public function indexItem(
        string $indexName,
        string $row
    ): bool {
        $item = json_decode(
            trim($row),
            true
        );

        $document = $this->elasticsearch->getDocument(
            $indexName,
            $item['_id']
        );

        if ($document) {
            $this->logDuplicationError(
                $document
            );
            return false;
        }

        $this->elasticsearch->index(
            $indexName,
            $item['_id'],
            $item['_source']
        );

        file_put_contents(
            'logs/last-restored.log',
            json_encode($item)
        );

        return true;
    }

    /**
     * Log duplication error.
     *
     * @param array $document
     * @return void
     */
    public function logDuplicationError(
        array $document
    ): void {
        file_put_contents(
            'logs/errors.log',
            json_encode($document) . "\n",
            FILE_APPEND
        );
        $this->errorsCount ++;
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
