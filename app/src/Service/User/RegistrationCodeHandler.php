<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\RegistrationCode;
use App\Entity\User;
use App\Repository\RegistrationCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

readonly class RegistrationCodeHandler
{
    public function __construct(
        private RegistrationCodeRepository $registrationCodeRepository,
        private EntityManagerInterface $entityManager,
        private ?string $registrationSecret = null,
    ) {
    }

    public function hasActiveRegistrationCodes(): bool
    {
        if (null !== $this->registrationSecret) {
            return true;
        }
        $activeCodes = $this->registrationCodeRepository->findActiveCodes();

        return [] !== $activeCodes;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function isValidCode(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        if ($value === $this->registrationSecret) {
            return true;
        }

        return $this->registrationCodeRepository->findValidCode($value) instanceof RegistrationCode;
    }

    public function needsCode(): bool
    {
        return is_string($this->registrationSecret);
    }

    public function setUserRegistrationCode(User $user, string $registrationCode): void
    {
        $registration = $this->registrationCodeRepository->findOneBy(['code' => $registrationCode]);
        if ($registration instanceof RegistrationCode) {
            $user->setRegistrationCode($registration);
            $this->entityManager->flush();
        }
    }

    public function getByRef(string $ref): ?RegistrationCode
    {
        return $this->registrationCodeRepository->findOneBy(['ref' => $ref]);
    }
}
