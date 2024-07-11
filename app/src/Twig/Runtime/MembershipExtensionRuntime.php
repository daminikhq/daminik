<?php

namespace App\Twig\Runtime;

use App\Entity\Membership;
use App\Service\User\MembershipHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class MembershipExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private MembershipHandlerInterface $membershipHandler,
        private TranslatorInterface $translator
    ) {
        // Inject dependencies if needed
    }

    public function highestRole(Membership $value): string
    {
        return $this->membershipHandler->getHighestWorkspaceRole($value)->trans($this->translator);
    }
}
