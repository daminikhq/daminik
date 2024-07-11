<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'static_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{domain}.{tld}')]
class StaticController extends AbstractController
{
    #[Route('legal', name: 'legal')]
    public function legal(
    ): Response {
        return $this->render('home/legal.html.twig');
    }
}
