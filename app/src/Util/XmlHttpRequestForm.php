<?php

declare(strict_types=1);

namespace App\Util;

use App\Dto\Response\FormResponse;
use App\Enum\FormStatus;
use Symfony\Component\Form\FormInterface;

class XmlHttpRequestForm
{
    /**
     * @param array<string, string|int|float|null>|null $body
     */
    public static function jsonResultFromForm(
        FormInterface $form,
        ?string $redirectTo = null,
        ?string $message = null,
        ?array $body = null
    ): FormResponse {
        $response = new FormResponse();
        if ($form->isSubmitted() && $form->isValid()) {
            $response->setStatus(FormStatus::OK);
        } else {
            $response->setStatus(FormStatus::ERROR);
            $validationErrors = [];
            if ($form->count()) {
                foreach ($form as $child) {
                    if ($child->isSubmitted() && !$child->isValid()) {
                        $validationErrors[$child->getName()] = self::getValidationErrors($child);
                    }
                }
            }

            $response->setValidation($validationErrors);
        }

        $response->setRedirectTo($redirectTo)
            ->setMessage($message)
            ->setBody($body);

        return $response;
    }

    /**
     * @return array<int, string>
     */
    private static function getValidationErrors(FormInterface $form): array
    {
        $validationErrors = [];
        foreach ($form->getErrors(deep: true) as $key => $error) {
            $validationErrors[$key] = strtr($error->getMessageTemplate(), $error->getMessageParameters());
        }

        return $validationErrors;
    }
}
