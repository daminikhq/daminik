<?php

namespace App\Validator;

use App\Entity\Workspace;
use App\Repository\WorkspaceRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AllowedWorkspaceSlugValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WorkspaceRepository $workspaceRepository,
        private readonly string $projectDir
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AllowedWorkspaceSlug) {
            throw new UnexpectedTypeException($constraint, AllowedWorkspaceSlug::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_numeric($value) && !is_string($value)) {
            return;
        }

        $value = (string) $value;

        $checkWorkspace = $this->workspaceRepository->findOneBy(['slug' => $value]);
        if ($checkWorkspace instanceof Workspace) {
            $this->violate($value, $constraint);

            return;
        }

        $fileContents = file_get_contents($this->projectDir.'/config/subdomain-blocklist.txt');
        if (!is_string($fileContents)) {
            throw new \RuntimeException();
        }
        $blocklist = explode("\n", $fileContents);
        if (in_array($value, $blocklist)) {
            $this->violate($value, $constraint);
        }
    }

    private function violate(string $value, AllowedWorkspaceSlug $constraint): void
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
