<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\User\AccountRequest;
use App\Entity\File;
use App\Exception\FileHandlerException;
use App\Exception\UserHandlerException;
use App\Exception\UsernameAlreadyTakenException;
use App\Form\User\AccountType;
use App\Message\Filesize\UpdateUploadSizesMessage;
use App\Message\PostUpload\CreateThumbnailMessage;
use App\Message\PostUpload\ReadMetadataMessage;
use App\Service\File\FileHandler;
use App\Service\User\AvatarHandler;
use App\Service\User\UserHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile', name: 'workspace_profile_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class ProfileController extends AbstractWorkspaceController
{
    /**
     * @throws UserHandlerException
     * @throws ExceptionInterface
     *
     * @noinspection DuplicatedCode
     */
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        UserHandlerInterface $userHandler,
        EntityManagerInterface $entityManager,
        FileHandler $fileHandler,
        MessageBusInterface $bus,
        AvatarHandler $avatarHandler
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $action = new AccountRequest($user, $request->getLocale());
        $avatar = $avatarHandler->getAvatar($user);
        $form = $this->createForm(AccountType::class, $action, [
            'avatar' => $avatar->getFile() instanceof File,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitButton = $form->get('submit');
            if (method_exists($submitButton, 'isClicked') && $submitButton->isClicked()) {
                try {
                    $userHandler->changeUsername($action);
                    $userHandler->changeName($action);
                    $userHandler->changeLocale($action);
                    $entityManager->flush();
                    if (null !== $action->getLocale()) {
                        $request->getSession()->set('_locale', $action->getLocale());
                    }

                    $uploadedFile = $form->get('avatar')->getData();
                    if ($uploadedFile instanceof UploadedFile) {
                        $file = $fileHandler->saveUserAvatar($user, $uploadedFile);
                        $entityManager->flush();
                        if (is_int($file->getId())) {
                            if (is_int($file->getActiveRevision()?->getId())) {
                                $bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $file->getActiveRevision()->getId()));
                            }
                            $bus->dispatch(new CreateThumbnailMessage($file->getId(), $file->getActiveRevision()?->getId()));
                            $bus->dispatch(new ReadMetadataMessage($file->getId(), $file->getActiveRevision()?->getId()));
                        }
                    }

                    return $this->redirectToRoute('workspace_profile_index');
                } catch (UsernameAlreadyTakenException $e) {
                    $form
                        ->get('username')
                        ->addError(new FormError($e->getMessage()));
                } catch (FileHandlerException $e) {
                    $form
                        ->get('avatar')
                        ->addError(new FormError($e->getMessage()));
                }
            } else {
                // resetAvatar-Button was clicked
                $avatarHandler->resetAvatar($user);
                $entityManager->flush();

                return $this->redirectToRoute('workspace_profile_index');
            }
        }

        return $this->render(view: 'home/profile.html.twig', parameters: [
            'form' => $form,
            'user' => $user,
            'workspace' => $workspace,
            'avatar' => $avatar,
        ]);
    }
}
