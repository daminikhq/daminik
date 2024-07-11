<?php

namespace App\Controller\Workspace;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'logger_fe_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class FeLoggerController extends AbstractWorkspaceController
{
    #[Route('/fe/logger', name: 'logger')]
    public function index(
        Request $request,
        LoggerInterface $uppyLogger
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $body = $request->toArray();
        $level = array_key_exists('level', $body) ? $body['level'] : LogLevel::DEBUG;
        $message = array_key_exists('message', $body) ? $body['message'] : '';
        if (!in_array($level, [LogLevel::DEBUG, LogLevel::WARNING, LogLevel::ERROR], true)) {
            $level = LogLevel::DEBUG;
        }
        if (!is_string($message)) {
            $message = '';
        }
        $uppyLogger->log($level, $message, [
            'workspace' => $workspace->getSlug(),
            'user' => $user->getId(),
        ]);

        $response = new Response('OK');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'code' => $response->getStatusCode(),
                'reason' => $response->getContent(),
            ]);
        }

        return $response;
    }
}
