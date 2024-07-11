<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AllowedWorkspaceSlug extends Constraint
{
    public string $message = 'The subdomain "{{ value }}" is either taken or not allowed.';
}
