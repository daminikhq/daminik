<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\Form\UserEdit;
use App\Entity\User;
use App\Enum\FlashType;
use App\Enum\UserRole;
use App\Enum\UserStatus;
use App\Form\Admin\UserEditType;
use App\Service\User\UserHandlerInterface;
use App\Util\User\RoleUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user', name: 'admin_user_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: 'admin.{domain}.{tld}')]
#[IsGranted(UserRole::SUPER_ADMIN->value)]
class UserController extends AbstractAdminController
{
    #[Route('/{userToEdit}', name: 'index')]
    public function index(
        User $userToEdit,
        Request $request,
        UserHandlerInterface $userHandler,
        TranslatorInterface $translator,
        RoleUtil $roleUtil
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $edit = (new UserEdit())
            ->setStatus(
                UserStatus::fromUser($userToEdit)
            )
            ->setRole($roleUtil->getHighestRole($userToEdit))
            ->setAdminNotice($userToEdit->getAdminNotice());

        $form = $this->createForm(UserEditType::class, $edit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userHandler->adminUpdateUser($userToEdit, $edit, $user);
            $this->addFlash(FlashType::SUCCESS->value, $translator->trans('admin.message.user.success.edit'));

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'userToEdit' => $userToEdit,
            'form' => $form->createView(),
        ]);
    }
}
