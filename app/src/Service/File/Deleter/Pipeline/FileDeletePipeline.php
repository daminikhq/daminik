<?php

declare(strict_types=1);

namespace App\Service\File\Deleter\Pipeline;

use App\Service\File\Deleter\Middleware\FileAssetCollectionDeleterMiddleware;
use App\Service\File\Deleter\Middleware\FileCategoryDeleterMiddleware;
use App\Service\File\Deleter\Middleware\FileDeleterMiddleware;
use App\Service\File\Deleter\Middleware\FileRevisionDeleterMiddleware;
use App\Service\File\Deleter\Middleware\FileTagDeleterMiddleware;
use App\Service\File\Deleter\Middleware\FileUserMetaDataDeleterMiddleware;
use App\Service\File\Deleter\MiddlewareInterface;
use App\Service\File\Deleter\MiddlewarePayloadInterface;
use App\Service\File\Deleter\MiddlewarePipelineInterface;
use App\Service\File\Deleter\Payload\FileDeletePayload;

readonly class FileDeletePipeline implements MiddlewarePipelineInterface
{
    /** @var MiddlewareInterface[] */
    private array $middleware;

    public function __construct(
        private FileTagDeleterMiddleware $fileTagDeleterMiddleware,
        private FileCategoryDeleterMiddleware $fileCategoryDeleterMiddleware,
        private FileAssetCollectionDeleterMiddleware $fileAssetCollectionDeleterMiddleware,
        private FileUserMetaDataDeleterMiddleware $fileUserMetaDataDeleterMiddleware,
        private FileRevisionDeleterMiddleware $fileRevisionDeleterMiddleware,
        private FileDeleterMiddleware $fileDeleterMiddleware,
    ) {
        $this->middleware = [
            $this->fileTagDeleterMiddleware,
            $this->fileCategoryDeleterMiddleware,
            $this->fileAssetCollectionDeleterMiddleware,
            $this->fileUserMetaDataDeleterMiddleware,
            $this->fileRevisionDeleterMiddleware,
            $this->fileDeleterMiddleware,
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function pipe(MiddlewarePayloadInterface $payload): MiddlewarePayloadInterface
    {
        assert($payload instanceof FileDeletePayload);
        $logData = [
          'id' => $payload->getFile()->getId(),
          'workspace' => $payload->getFile()->getWorkspace()?->getSlug(),
          'filename' => $payload->getFile()->getFilename(),
            ];
        $payload->getLogger()->info('Delete-Pipeline started', $logData);
        foreach ($this->middleware as $middleware) {
            $middlewareShortname = (new \ReflectionClass($middleware))->getShortName();
            $payload->getLogger()->info(sprintf('Middleware %s started', $middlewareShortname));
            $payload = $middleware->pipe($payload);
            assert($payload instanceof FileDeletePayload);
            $payload->getLogger()->info(sprintf('Middleware %s finished', $middlewareShortname));
        }
        $payload->getLogger()->info('Delete-Pipeline finished', $logData);

        return $payload;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
