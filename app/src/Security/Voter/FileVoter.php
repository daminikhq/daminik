<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\File;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FileVoter extends Voter
{
    public const DELETE = 'FILE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        /*
         * @noinspection PhpInArrayCanBeReplacedWithComparisonInspection
         * @noinspection InArrayMissUseInspection
         */
        return self::DELETE === $attribute
            && ($subject instanceof File || null === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof File) {
            return false;
        }
        $user = $token->getUser();

        return $subject->getUploader() === $user;
    }
}
