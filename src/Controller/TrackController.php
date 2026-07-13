<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TrackController extends AbstractController
{
    /**
     * Page publique de suivi de commande.
     * L'URL publique du Hub Mercure est injectée pour l'abonnement temps réel côté navigateur.
     */
    #[Route('/', name: 'track_home', methods: ['GET'])]
    public function index(
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')] string $mercurePublicUrl,
    ): Response {
        return $this->render('track/index.html.twig', [
            'mercure_public_url' => $mercurePublicUrl,
        ]);
    }
}
