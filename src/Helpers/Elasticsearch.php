<?php

namespace ElasticNomad\Helpers;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;

class Elasticsearch
{
    private $settings = [];

    public function __construct()
    {
        $this->settings = [
            'host' => getenv('ELASTICSEARCH_HOST') ?? '',
            'username' => getenv('ELASTICSEARCH_USERNAME') ?? '',
            'password' => getenv('ELASTICSEARCH_PASSWORD') ?? '',
        ];
    }

    /**
     * Index document.
     *
     * @param string $index
     * @param string $id
     * @param array $body
     * @return bool
     */
    public function index(
        string $index,
        string $id,
        array $body
    ): bool {
        try {
            $client = $this->newElasticsearchClient();
            $client->index([
                'index' => $index,
                'id' => $id,
                'body' => $body,
            ]);

            return true;
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    /**
     * Get document.
     *
     * @param string $index
     * @param string $id
     * @return array
     */
    public function getDocument(
        string $index,
        string $id
    ): array {
        try {
            $client = $this->newElasticsearchClient();
            $response = $client->get([
                'index' => $index,
                'id' => $id,
            ]);

            return $response;
        } catch (Exception $error) {
            return [];
        }
    }

    /**
     * Search documents.
     *
     * @param string $index
     * @param array $body
     * @param int $size
     * @return array
     */
    public function search(
        string $index,
        array $body,
        int $size
    ): array {
        try {
            $client = $this->newElasticsearchClient();
            $response = $client->search([
                'scroll' => '1m',
                'size' => $size,
                'body' => $body,
                'index' => $index,
            ]);

            return $response;
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    /**
     * Scroll search for documents.
     *
     * @param string $scrollId
     * @return array
     */
    public function scroll(
        string $scrollId
    ): array {
        try {
            $client = $this->newElasticsearchClient();
            $response = $client->scroll([
                'body' => [
                    'scroll_id' => $scrollId,
                    'scroll' => '1m',
                ],
            ]);

            return $response;
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    /**
     * Get new ElasticsearchClient object.
     *
     * @return Client
     */
    public function newElasticsearchClient(): Client
    {
        $client = ClientBuilder::create()
            ->setHosts([
                $this->settings['host'],
            ]);

        if (
            !empty($this->settings['username']) &&
            !empty($this->settings['password'])
        ) {
            $client->setBasicAuthentication(
                $this->settings['username'],
                $this->settings['password']
            );
        }

        return $client->build();
    }
}
