<?php

namespace App\Controller\Admin;

use App\Dto\Utility\DefaultRequestValues;
use App\Entity\RegistrationCode;
use App\Entity\User;
use App\Enum\UserRole;
use App\Form\Admin\RegistrationCodeType;
use App\Repository\RegistrationCodeRepository;
use App\Service\User\UserHandlerInterface;
use App\Util\RequestArgumentHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/registrationcode', name: 'admin_registrationcode_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: 'admin.{domain}.{tld}')]
#[IsGranted(UserRole::SUPER_ADMIN->value)]
class RegistrationCodeController extends AbstractAdminController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(RegistrationCodeRepository $registrationCodeRepository): Response
    {
        return $this->render('admin/registration_code/index.html.twig', [
            'registration_codes' => $registrationCodeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $registrationCode = new RegistrationCode();
        $form = $this->createForm(RegistrationCodeType::class, $registrationCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registrationCode->setCreatedBy($user);
            $entityManager->persist($registrationCode);
            $entityManager->flush();

            return $this->redirectToRoute('admin_registrationcode_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/registration_code/new.html.twig', [
            'registration_code' => $registrationCode,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        RegistrationCode $registrationCode,
        UserHandlerInterface $userHandler,
        Request $request
    ): Response {
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(limit: 100)
        );
        $users = $userHandler->filterAndPaginateUsers(
            $arguments,
            $registrationCode
        );

        return $this->render('admin/registration_code/show.html.twig', [
            'registration_code' => $registrationCode,
            'users' => $users,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RegistrationCode $registrationCode, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RegistrationCodeType::class, $registrationCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_registrationcode_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/registration_code/edit.html.twig', [
            'registration_code' => $registrationCode,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, RegistrationCode $registrationCode, EntityManagerInterface $entityManager): Response
    {
        $token = $request->getPayload()->get('_token');
        if (!is_string($token)) {
            $token = null;
        }
        if ($this->isCsrfTokenValid('delete'.$registrationCode->getId(), $token)) {
            $entityManager->remove($registrationCode);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_registrationcode_index', [], Response::HTTP_SEE_OTHER);
    }
}
