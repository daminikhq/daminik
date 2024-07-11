<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Adds a new super admin',
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly EmailVerifier $emailVerifier,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    private function validateEmail(?string $email): void
    {
        $result = $this->validator->validate($email, [new NotNull(), new Email()]);
        if ($result->count() > 0) {
            throw new \RuntimeException($result->get(0)->getMessage(), (int) $result->get(0)->getCode());
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $email */
        $email = $io->ask(question: 'Email', validator: function (?string $answer): string {
            $this->validateEmail($answer);

            return (string) $answer;
        });

        $adminToBe = $this->userRepository->findOneBy(['email' => $email]);
        if ($adminToBe instanceof User) {
            $roles = $adminToBe->getRoles();
            if (!in_array(UserRole::SUPER_ADMIN->value, $roles, true)) {
                $roles[] = UserRole::SUPER_ADMIN->value;
                $adminToBe->setRoles($roles);
            }
            $this->entityManager->flush();
            $io->success(sprintf('%s is now a super admin', $adminToBe->getUsername() ?? $adminToBe->getName() ?? $adminToBe->getUserIdentifier()));

            return self::SUCCESS;
        }

        $password = $io->askHidden('Password');

        if (!is_string($password)) {
            throw new \RuntimeException();
        }

        $user = (new User())
            ->setEmail($email)
            ->setRoles([UserRole::SUPER_ADMIN->value]);
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $password
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        try {
            $this->emailVerifier->sendEmailConfirmation(verifyEmailRouteName: 'verify_email', user: $user);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Your new super admin account has been created, you should get an email to confirm');

        return Command::SUCCESS;
    }
}
