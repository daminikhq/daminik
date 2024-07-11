<?php

namespace App\Validator;

use App\Service\User\RegistrationCodeHandler;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsValidRegistrationCodeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly RegistrationCodeHandler $registrationCodeHandler
    ) {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsValidRegistrationCode) {
            throw new UnexpectedTypeException($constraint, IsValidRegistrationCode::class);
        }

        if (!is_string($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        if (!$this->registrationCodeHandler->isValidCode($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
