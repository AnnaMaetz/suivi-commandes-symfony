<?php

namespace App\Controller\Api;

use App\Repository\CustomerOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    #[Route('/{trackingCode}', name: 'api_order_show', methods: ['GET'])]
    public function show(string $trackingCode, CustomerOrderRepository $repo): JsonResponse
    {
        $order = $repo->findOneBy(['trackingCode' => $trackingCode]);

        if (!$order) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        return $this->json([
            'trackingCode' => $order->getTrackingCode(),
            'customerName' => $order->getCustomerName(),
            'status' => $order->getStatus(),
        ]);
    }
}