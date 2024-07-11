<?php

declare(strict_types=1);

namespace App\Listener;

use App\Exception\Workspace\WorkspaceBlockedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class ExceptionListener
{
    public function __construct(
        private Environment $twig
    ) {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $response = null;

        if ($event->getThrowable() instanceof WorkspaceBlockedException) {
            $response = new Response($this->twig->render('exception/workspace_blocked.html.twig'), 401);
        }

        if ($response instanceof Response) {
            $event->setResponse($response);
        }
    }
}
