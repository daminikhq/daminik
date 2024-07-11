<?php

namespace App\Controller\Workspace;

use App\Enum\MimeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

#[Route('', name: 'workspace_jsconfig_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class JsConfigController extends AbstractWorkspaceController
{
    #[Route('/filetypes.json', name: 'filetypes')]
    public function filetypes(): Response
    {
        return $this->json([
            'filetypes' => MimeType::validUploadExtensions(),
        ])->setSharedMaxAge(600);
    }

    #[Route('/translation.js', name: 'translation')]
    public function translations(
        Request $request,
        string $projectDir
    ): Response {
        $locale = $request->getLocale();
        $file = sprintf('%s/translations/messages+intl-icu.%s.yml', $projectDir, $locale);
        if (!file_exists($file)) {
            throw $this->createNotFoundException();
        }
        $fileContents = file_get_contents($file);
        if (false === $fileContents) {
            throw $this->createNotFoundException();
        }
        $parsed = Yaml::parse($fileContents);

        $translations = $this->renderView(
            'jsconfig/translation.js.twig',
            ['json' => json_encode($parsed)]
        );

        return new Response($translations, 200,
            ['Content-Type' => 'text/javascript']
        );
    }
}
