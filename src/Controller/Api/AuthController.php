<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    /**
     * Point d'entrée de connexion.
     *
     * Le corps de cette méthode n'est jamais exécuté : le firewall "login"
     * (json_login) intercepte la requête, vérifie email + mot de passe, et
     * renvoie un token JWT via le handler de Lexik. La route doit néanmoins
     * exister pour que la requête atteigne le firewall.
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw new \LogicException('This should be intercepted by the json_login firewall.');
    }
}
