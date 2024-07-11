<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Workspace\AbstractWorkspaceController;
use App\Entity\File;
use App\Entity\Revision;
use App\Enum\FilesystemType;
use App\Enum\WorkspaceStatus;
use App\Exception\File\GetterException;
use App\Exception\File\MissingFilenameSlugException;
use App\Exception\FileHandlerException;
use App\Message\PostUpload\CreateThumbnailMessage;
use App\Service\File\FileHandler;
use App\Service\File\GetterInterface;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/file', name: 'public_file_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class PublicFileController extends AbstractWorkspaceController
{
    public function __construct(
        private readonly FileHandler $fileHandler,
        private readonly GetterInterface $fileGetter,
        private readonly UrlHelperInterface $urlHelper,
        protected WorkspaceIdentifier $workspaceIdentifier
    ) {
        parent::__construct($workspaceIdentifier);
    }

    /**
     * @noinspection DuplicatedCode
     *
     * @throws GetterException
     */
    #[Route('/{filename}', name: 'file')]
    public function download(
        string $filename,
        Request $request
    ): Response {
        $workspace = $this->getWorkspace();
        if ($workspace->getStatus() === WorkspaceStatus::BLOCKED->value) {
            throw $this->createNotFoundException();
        }

        $file = $this->fileGetter->getFile(workspace: $workspace, filename: $filename);
        if (!$file instanceof File || null === $file->getFilename()) {
            throw $this->createNotFoundException();
        }

        if (true !== $file->isPublic()) {
            $timestamp = $request->get('timestamp');
            $hash = $request->get('hash');
            if (!$this->urlHelper->validateTimestampAndHash($file, $timestamp, $hash)) {
                throw $this->createNotFoundException();
            }
        }

        try {
            $response = new Response();
            $response->setStatusCode(200);
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->getFilename());
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', $this->fileHandler->getMime($file));
            $response->headers->set('Cache-Control', 'max-age=60');
            $response->setContent($this->fileHandler->getFileContent($file));
        } catch (\Throwable) {
            throw $this->createNotFoundException();
        }

        return $response;
    }

    /** @noinspection DuplicatedCode */
    /**
     * @throws MissingFilenameSlugException
     * @throws FileHandlerException
     * @throws GetterException
     * @throws ExceptionInterface
     */
    #[Route('/thumbnail/{filename}', name: 'thumbnail')]
    public function thumbnail(
        string $filename,
        Request $request,
        MessageBusInterface $bus,
    ): Response {
        $width = null;
        $height = 440;
        $workspace = $this->getWorkspace();

        $file = $this->fileGetter->getFile(workspace: $workspace, filename: $filename);
        if (!$file instanceof File || null === $file->getFilename()) {
            throw $this->createNotFoundException();
        }

        $revisionEntity = $file->getActiveRevision();

        if (!$revisionEntity instanceof Revision) {
            throw $this->createNotFoundException();
        }

        if (!$this->fileHandler->sizeExists($file, $width, $height, $revisionEntity)) {
            if (null !== $file->getId()) {
                $bus->dispatch(new CreateThumbnailMessage($file->getId(), $revisionEntity->getId()));
            }

            return $this->redirect('/images/platzhalter.png');
        }

        if (true !== $file->isPublic()) {
            $timestamp = $request->get('timestamp');
            $hash = $request->get('hash');
            if (!$this->urlHelper->validateTimestampAndHash($file, $timestamp, $hash)) {
                throw $this->createNotFoundException();
            }
        }

        if ($workspace->getFilesystem()?->getType() === FilesystemType::S3->value) {
            $thumbnailUrl = $this->urlHelper->getThumbnailUrl($file, $revisionEntity->getCounter());
            if (null !== $thumbnailUrl) {
                return $this->redirect($thumbnailUrl);
            }
        }

        try {
            $response = new Response();
            $response->setStatusCode(200);
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->getFilename());
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', $this->fileHandler->getMime($file));
            $response->headers->set('Cache-Control', 'max-age=60');
            $response->setContent($this->fileHandler->getResizedContent($file, $width, $height, true, $revisionEntity));
        } catch (\Throwable) {
            throw $this->createNotFoundException();
        }

        return $response;
    }
}
