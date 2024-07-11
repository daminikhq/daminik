<?php

declare(strict_types=1);

namespace App\Service\Ai\Imagga;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class Client
{
    private string $apiBaseUrl = 'https://api.imagga.com/v2';

    public function __construct(
        private readonly string $key,
        private readonly string $secret,
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * @throws ImaggaException
     */
    public function uploadFile(string $localPath): string
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                $this->apiBaseUrl.'/uploads',
                [
                    'body' => ['image' => fopen(filename: $localPath, mode: 'rb')],
                    'auth_basic' => [
                        $this->key,
                        $this->secret,
                    ],
                ]
            );
            $responseJson = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (
                is_array($responseJson)
                && array_key_exists('result', $responseJson)
                && is_array($responseJson['result'])
                && array_key_exists('upload_id', $responseJson['result'])
                && is_string($responseJson['result']['upload_id'])
            ) {
                return $responseJson['result']['upload_id'];
            }
        } catch (\Throwable $e) {
            throw new ImaggaException($e->getMessage(), $e->getCode(), $e);
        }
        throw new ImaggaException();
    }

    /**
     * @return array<string, array<string, mixed>>
     *
     * @throws ImaggaException
     */
    public function getTags(string $identifier, string $language): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->apiBaseUrl.'/tags',
                [
                    'query' => [
                        'image_upload_id' => $identifier,
                        'language' => $language,
                    ],
                    'auth_basic' => [
                        $this->key,
                        $this->secret,
                    ],
                ]
            );
            $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($content)) {
                return $content;
            }
        } catch (\Throwable $e) {
            throw new ImaggaException($e->getMessage(), $e->getCode(), $e);
        }
        throw new ImaggaException();
    }

    public function hasConfig(): bool
    {
        return '' !== $this->key && '' !== $this->secret;
    }
}
