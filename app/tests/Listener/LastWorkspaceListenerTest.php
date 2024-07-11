<?php

namespace App\Tests\Listener;

use App\Entity\User;
use App\Entity\Workspace;
use App\Listener\LastWorkspaceListener;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LastWorkspaceListenerTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private TokenStorageInterface&MockObject $tokenStorage;
    private WorkspaceRepository&MockObject $workspaceRepository;
    private EntityManagerInterface&MockObject $entityManager;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->workspaceRepository = $this->createMock(WorkspaceRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testSubRequest(): void
    {
        $this->dispatcher->addSubscriber(
            new LastWorkspaceListener(
                tokenStorage: $this->tokenStorage,
                workspaceRepository: $this->workspaceRepository,
                entityManager: $this->entityManager
            )
        );

        $event = new RequestEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            request: $this->createMock(Request::class),
            requestType: HttpKernelInterface::SUB_REQUEST
        );

        $this->tokenStorage->expects(self::never())->method('getToken');

        $this->dispatcher->dispatch($event);
    }

    public function testMainRequestWithoutSubdomain(): void
    {
        $this->dispatcher->addSubscriber(
            new LastWorkspaceListener(
                tokenStorage: $this->tokenStorage,
                workspaceRepository: $this->workspaceRepository,
                entityManager: $this->entityManager
            )
        );

        $request = new Request();

        $event = new RequestEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            request: $request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $user = new User();
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $this->tokenStorage->expects(self::once())->method('getToken')->willReturn($token);

        $this->workspaceRepository->expects(self::never())->method('findOneBy');

        $this->dispatcher->dispatch($event);
        self::assertNull($user->getLastUsedWorkspace());
    }

    public function testMainRequestWithSubdomain(): void
    {
        $this->dispatcher->addSubscriber(
            new LastWorkspaceListener(
                tokenStorage: $this->tokenStorage,
                workspaceRepository: $this->workspaceRepository,
                entityManager: $this->entityManager
            )
        );

        $request = new Request();
        $request->attributes->set('subdomain', 'test');

        $event = new RequestEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            request: $request,
            requestType: HttpKernelInterface::MAIN_REQUEST
        );

        $user = new User();
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $this->tokenStorage->expects(self::once())->method('getToken')->willReturn($token);

        $workspace = new Workspace();

        $this->workspaceRepository->expects(self::once())->method('findOneBy')->willReturn($workspace);

        $this->dispatcher->dispatch($event);
        self::assertSame($workspace, $user->getLastUsedWorkspace());
    }
}
