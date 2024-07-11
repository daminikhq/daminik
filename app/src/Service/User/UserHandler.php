<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Dto\Admin\Form\UserEdit;
use App\Dto\User\Interface\LocaleRequestChangeInterface;
use App\Dto\User\Interface\NameRequestInterface;
use App\Dto\User\Interface\UsernameRequestInterface;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserAction;
use App\Exception\UserHandlerException;
use App\Exception\UsernameAlreadyTakenException;
use App\Repository\FileRepository;
use App\Repository\UserRepository;
use App\Service\DatabaseLogger\DatabaseLogger;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class UserHandler implements UserHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private FileRepository $fileRepository,
        private CacheInterface $cache,
        private Paginator $paginator,
        private EntityManagerInterface $entityManager,
        private DatabaseLogger $databaseLogger,
    ) {
    }

    /**
     * @throws UserHandlerException
     */
    public function changeName(NameRequestInterface $action): void
    {
        $name = $action->getName();
        if (!is_string($name)) {
            throw new UserHandlerException();
        }

        $user = $action->getUser();

        $user->setName($name);
    }

    /**
     * @throws UserHandlerException
     * @throws UsernameAlreadyTakenException
     */
    public function changeUsername(UsernameRequestInterface $action): void
    {
        $username = $action->getUsername();
        if (!is_string($username)) {
            throw new UserHandlerException();
        }

        $user = $action->getUser();

        $checkUser = $this->userRepository->findBy(['username' => $username]);
        if (0 !== count($checkUser) && $checkUser[0] !== $user) {
            throw new UsernameAlreadyTakenException();
        }

        $user->setUsername($username);
    }

    public function changeLocale(LocaleRequestChangeInterface $action): void
    {
        $user = $action->getUser();
        $user->setLocale($action->getLocale());
    }

    /**
     * @return User[]
     *
     * @throws InvalidArgumentException
     */
    public function getForAutocomplete(Workspace $workspace, ?string $query = null, int $limit = 10, bool $cached = true): array
    {
        if ($cached) {
            $cacheKey = sprintf('autocomplete-user-%s-%s', $workspace->getSlug(), $query);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($workspace, $query, $limit) {
                $item->expiresAfter(new \DateInterval('PT30S'));

                return $this->getForAutocomplete($workspace, $query, $limit, false);
            });
        }

        $workspaceUserUploadCounts = $this->getWorkspaceUserUploadCounts($workspace, $cached);
        $ids = array_map(static fn (array $count) => $count['uploader'], $workspaceUserUploadCounts);

        return $this->userRepository->findForAutocomplete($ids, $query, $limit);
    }

    /**
     * @return array<int, array{uploader: int, filecount: int}>
     *
     * @throws InvalidArgumentException
     */
    public function getWorkspaceUserUploadCounts(Workspace $workspace, bool $cached = true): array
    {
        if ($cached) {
            $cacheKey = sprintf('autocomplete-user-uploadcount-%s', $workspace->getSlug());

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($workspace) {
                $item->expiresAfter(new \DateInterval('PT5M'));

                return $this->getWorkspaceUserUploadCounts($workspace, false);
            });
        }

        return $this->fileRepository->findForUserAutocomplete($workspace);
    }

    /**
     * @throws PaginatorException
     */
    public function filterAndPaginateUsers(SortFilterPaginateArguments $sortFilterPaginateArguments): Paginator
    {
        return $this->paginator->paginate(
            query: $this->userRepository->getUserQuery($sortFilterPaginateArguments->getSort()),
            page: $sortFilterPaginateArguments->getPage(),
            limit: $sortFilterPaginateArguments->getLimit()
        );
    }

    public function adminUpdateUser(User $userToEdit, UserEdit $edit, User $user): void
    {
        $userToEdit->setStatus($edit->getStatus()->value)
            ->setRoles([$edit->getRole()->value])
            ->setAdminNotice($edit->getAdminNotice());
        $this->databaseLogger->log(userAction: UserAction::UPDATE_USER, object: $userToEdit);
        $this->entityManager->flush();
    }
}
