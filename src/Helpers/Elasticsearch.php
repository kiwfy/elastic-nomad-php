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

    public function index(
        string $index,
        string $id,
        array $body
    ): array {
        try {
            $client = $this->newElasticsearchClient();
            $response = $client->index([
                'index' => $index,
                'id' => $id,
                'body' => $body,
            ]);

            return $response;
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

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
