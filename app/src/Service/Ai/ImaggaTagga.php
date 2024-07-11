<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Dto\File\AiTag;
use App\Entity\File;
use App\Entity\User;
use App\Service\Ai\Imagga\Client;
use App\Service\Ai\Imagga\ImaggaException;
use App\Service\File\FileHandler;

readonly class ImaggaTagga implements AiTaggerInterface
{
    public function __construct(
        private FileHandler $fileHandler,
        private Client $client
    ) {
    }

    /**
     * @throws ImaggaException
     */
    public function tag(File $file, User $user, bool $update = false): File
    {
        if (false === $update && null !== $file->getAiTags()) {
            return $file;
        }

        try {
            $localPath = $this->fileHandler->provideLocalFile($file);
        } catch (\Throwable $e) {
            throw new ImaggaException($e->getMessage(), $e->getCode(), $e);
        }
        if (null === $localPath) {
            throw new ImaggaException();
        }

        $identifier = $this->client->uploadFile($localPath);
        $language = $file->getWorkspace()?->getLocale() ?? $user->getLocale() ?? 'de';
        $tags = $this->client->getTags($identifier, $language);
        $aiTags = [];

        if (
            array_key_exists('result', $tags)
            && is_array($tags['result'])
            && array_key_exists('tags', $tags['result'])
            && is_array($tags['result']['tags'])) {
            foreach ($tags['result']['tags'] as $rawTag) {
                if (is_array($rawTag)) {
                    $confidence = 0.0;
                    if (
                        array_key_exists('confidence', $rawTag) && is_numeric($rawTag['confidence'])
                    ) {
                        $confidence = (float) $rawTag['confidence'];
                    }
                    if (
                        array_key_exists('tag', $rawTag)
                        && is_array($rawTag['tag'])
                    ) {
                        foreach ($rawTag['tag'] as $value) {
                            if (is_string($value)) {
                                $aiTags[] = new AiTag(tag: $value, confidence: $confidence);
                            }
                        }
                    }
                }
            }
        }

        if ([] !== $aiTags) {
            $file->setAiTags($aiTags);
        }

        try {
            $this->fileHandler->removeLocalFile($file);
        } catch (\Throwable $e) {
            throw new ImaggaException($e->getMessage(), $e->getCode(), $e);
        }

        return $file;
    }
}
