<?php

namespace ElasticNomad\Helpers;

use Aws\S3\S3Client;
use Exception;

class S3
{
    private $settings = [];

    public function __construct()
    {
        $this->settings = [
            'version' => getenv('S3_VERSION') ?? '',
            'region' => getenv('S3_REGION') ?? '',
            'credentials' => [
                'key' => getenv('AWS_KEY') ?? '',
                'secret' => getenv('AWS_SECRET') ?? '',
            ],
        ];
    }

    public function download(
        string $bucket,
        string $key,
        string $localPath
    ): void {
        try {
            $s3Client = $this->newS3Client();
            $s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SaveAs' => $localPath,
            ]);
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    public function uploadFile(
        string $bucket,
        string $key,
        string $localPath
    ): void {
        $body = fopen(
            $localPath,
            'rb'
        );

        try {
            $s3Client = $this->newS3Client();
            $s3Client->upload(
                $bucket,
                $key,
                $body,
                'private'
            );
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    public function newS3Client(): S3Client
    {
        return new S3Client(
            $this->settings
        );
    }
}
