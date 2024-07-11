<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Entity\File;
use App\Entity\Revision;
use App\Enum\FilesystemType;
use App\Exception\File\GetterException;
use App\Exception\File\MissingFilenameSlugException;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Message\PostUpload\CreateThumbnailMessage;
use App\Message\PostUpload\CreateWorkspaceIconMessage;
use App\Repository\WorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use App\Service\File\FileHandler;
use App\Service\File\GetterInterface;
use App\Service\File\Helper\FileHelper;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(WorkspaceVoter::VIEW_ASSET)]
#[Route('/download', name: 'workspace_download_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class DownloadController extends AbstractWorkspaceController
{
    public function __construct(
        private readonly GetterInterface $fileGetter,
        private readonly FileHandler $fileHandler,
        WorkspaceIdentifier $workspaceIdentifier
    ) {
        parent::__construct($workspaceIdentifier);
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     * @throws GetterException
     *
     * @noinspection DuplicatedCode
     */
    #[Route('/{filename}', name: 'file')]
    public function download(string $filename, UrlHelperInterface $urlHelper): Response
    {
        $workspace = $this->getWorkspace();

        $file = $this->fileGetter->getFile(workspace: $workspace, filename: $filename, includeDeleted: true);
        if (!$file instanceof File || null === $file->getFilename()) {
            throw $this->createNotFoundException();
        }

        if ($workspace->getFilesystem()?->getType() === FilesystemType::S3->value) {
            $url = $urlHelper->getPrivateUrl($file);
            if (null !== $url) {
                return $this->redirect($url);
            }
        }

        $response = new Response();
        $response->setStatusCode(200);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->getFilename());
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $this->fileHandler->getMime($file));
        $response->headers->set('Cache-Control', 'max-age=60');
        $response->setContent($this->fileHandler->getFileContent($file));

        return $response;
    }

    /**
     * @throws FileHandlerException
     * @throws MissingFilenameSlugException
     * @throws MissingWorkspaceException
     * @throws ExceptionInterface
     */
    #[Route('/icon/{workspaceSlug}', name: 'icon')]
    public function icon(
        string $workspaceSlug,
        WorkspaceRepository $workspaceRepository,
        UrlHelperInterface $urlHelper,
        MessageBusInterface $bus
    ): Response {
        $width = 256;
        $height = 256;
        $workspace = $workspaceRepository->findOneBy(['slug' => $workspaceSlug]);
        $iconFile = $workspace?->getIconFile();
        if (null === $workspace || null === $iconFile) {
            throw $this->createNotFoundException();
        }
        if (!$this->fileHandler->sizeExists($iconFile, $width, $height) && is_int($workspace->getId())) {
            $bus->dispatch(new CreateWorkspaceIconMessage($workspace->getId()));

            throw $this->createNotFoundException();
        }

        if ($workspace->getFilesystem()?->getType() === FilesystemType::S3->value) {
            $workspaceIcon = $urlHelper->getWorkspaceIcon($iconFile);
            if (null !== $workspaceIcon) {
                return $this->redirect($workspaceIcon);
            }
        }

        $response = new Response();
        $response->setStatusCode(200);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, sprintf('%s-icon.png', $workspace->getSlug()));
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'max-age=60');
        $response->setContent($this->fileHandler->getResizedContent($iconFile, $width, $height));

        return $response;
    }

    /**
     * @throws FileHandlerException
     * @throws MissingFilenameSlugException
     * @throws MissingWorkspaceException
     * @throws GetterException
     * @throws ExceptionInterface
     */
    #[Route('/thumbnail/{filename}', name: 'thumbnail')]
    #[Route('/thumbnail/revision/{revision}/{filename}', name: 'revision_thumbnail')]
    public function thumbnail(
        string $filename,
        UrlHelperInterface $urlHelper,
        MessageBusInterface $bus,
        ?int $revision = null
    ): Response {
        $width = null;
        $height = 440;
        $workspace = $this->getWorkspace();

        $file = $this->fileGetter->getFile(workspace: $workspace, filename: $filename, includeDeleted: true);
        if (!$file instanceof File || null === $file->getFilename()) {
            throw $this->createNotFoundException();
        }

        if (null === $revision) {
            $revision = $file->getActiveRevision()?->getCounter();
        }

        $revisionEntity = FileHelper::getRevision($file, $revision);
        if (!$revisionEntity instanceof Revision && null !== $revision) {
            throw $this->createNotFoundException();
        }

        if (!$this->fileHandler->sizeExists($file, $width, $height, $revisionEntity)) {
            if (null !== $file->getId()) {
                $bus->dispatch(new CreateThumbnailMessage($file->getId(), $revisionEntity?->getId()));
            }

            return $this->redirect('/images/platzhalter.png');
        }

        if ($workspace->getFilesystem()?->getType() === FilesystemType::S3->value) {
            $thumbnailUrl = $urlHelper->getThumbnailUrl($file, $revision);
            if (null !== $thumbnailUrl) {
                return $this->redirect($thumbnailUrl);
            }
        }

        $response = new Response();
        $response->setStatusCode(200);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, sprintf('%s-thumb.png', $file->getPublicFilenameSlug()));
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'max-age=60');
        $response->setContent($this->fileHandler->getResizedContent($file, $width, $height, true, $revisionEntity));

        return $response;
    }
}
