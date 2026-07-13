<?php

namespace App\Controller\Api;

use App\Entity\CustomerOrder;
use App\Entity\OrderStatusHistory;
use App\Enum\OrderStatus;
use App\Repository\CustomerOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'api_order_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CustomerOrderRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données reçues
        if (!is_array($data) || !isset($data['customerName']) || !is_string($data['customerName'])) {
            return $this->json(['error' => 'Le champ "customerName" est requis.'], 400);
        }

        $customerName = trim($data['customerName']);
        if ($customerName === '' || mb_strlen($customerName) > 100) {
            return $this->json(['error' => 'Le champ "customerName" doit faire entre 1 et 100 caractères.'], 400);
        }

        // Création de la commande
        $order = new CustomerOrder();
        $order->setTrackingCode($this->generateUniqueTrackingCode($repo));
        $order->setCustomerName($customerName);
        $order->setStatus(OrderStatus::Created);

        // Première entrée d'historique : la commande vient d'être créée
        $history = new OrderStatusHistory();
        $history->setStatus(OrderStatus::Created);
        $history->setNote('Commande créée');
        $history->setChangedAt(new \DateTimeImmutable());
        $history->setCustomerOrder($order);

        // Enregistrement en base
        $em->persist($order);
        $em->persist($history);
        $em->flush();

        return $this->json([
            'trackingCode' => $order->getTrackingCode(),
            'customerName' => $order->getCustomerName(),
            'status' => $order->getStatus()->value,
            'statusLabel' => $order->getStatus()->label(),
        ], 201);
    }

    /**
     * Génère un code de suivi de 12 caractères, unique en base.
     * Alphabet volontairement sans caractères ambigus (pas de O/0, I/1).
     */
    private function generateUniqueTrackingCode(CustomerOrderRepository $repo): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($alphabet) - 1;

        do {
            $code = '';
            for ($i = 0; $i < 12; $i++) {
                $code .= $alphabet[random_int(0, $maxIndex)];
            }
        } while ($repo->findOneBy(['trackingCode' => $code]) !== null);

        return $code;
    }

    #[Route('/{id<\d+>}/status', name: 'api_order_update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request, EntityManagerInterface $em, CustomerOrderRepository $repo, HubInterface $hub): JsonResponse
    {
        $order = $repo->find($id);

        if (!$order) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['status']) || !is_string($data['status'])) {
            return $this->json(['error' => 'Le champ "status" est requis.'], 400);
        }

        // Le statut envoyé correspond-il à une valeur connue de l'enum ?
        $newStatus = OrderStatus::tryFrom($data['status']);

        if ($newStatus === null) {
            return $this->json([
                'error' => 'Statut invalide.',
                'allowed' => array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases()),
            ], 400);
        }

        // La transition est-elle autorisée (étape suivante uniquement) ?
        $current = $order->getStatus();

        if (!$current->canTransitionTo($newStatus)) {
            $next = $current->next();

            return $this->json([
                'error' => sprintf(
                    'Transition invalide : une commande "%s" ne peut passer qu\'à "%s".',
                    $current->value,
                    $next?->value ?? '(aucun — statut final)',
                ),
            ], 422);
        }

        // Note optionnelle accompagnant le changement de statut
        $note = isset($data['note']) && is_string($data['note']) ? trim($data['note']) : null;
        if ($note === '') {
            $note = null;
        }

        // Application du changement + nouvelle entrée d'historique
        $order->setStatus($newStatus);
        $order->setUpdatedAt(new \DateTimeImmutable());

        $history = new OrderStatusHistory();
        $history->setStatus($newStatus);
        $history->setNote($note);
        $history->setChangedAt(new \DateTimeImmutable());
        $history->setCustomerOrder($order);

        $em->persist($history);
        $em->flush();

        // Publication de l'event temps réel vers le Hub Mercure.
        // Le topic "order/{trackingCode}" est le canal auquel le front s'abonnera.
        $update = new Update(
            sprintf('order/%s', $order->getTrackingCode()),
            (string) json_encode([
                'trackingCode' => $order->getTrackingCode(),
                'status' => $newStatus->value,
                'statusLabel' => $newStatus->label(),
                'changedAt' => $history->getChangedAt()->format(\DateTimeInterface::ATOM),
            ]),
        );

        $hub->publish($update);

        return $this->json([
            'trackingCode' => $order->getTrackingCode(),
            'customerName' => $order->getCustomerName(),
            'status' => $order->getStatus()->value,
            'statusLabel' => $order->getStatus()->label(),
        ]);
    }

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
            'status' => $order->getStatus()?->value,
            'statusLabel' => $order->getStatus()?->label(),
        ]);
    }
}