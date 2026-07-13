<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Created = 'created';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';

    /**
     * Libellé lisible (français) du statut, pour l'affichage côté client.
     */
    public function label(): string
    {
        return match ($this) {
            OrderStatus::Created => 'Commande créée',
            OrderStatus::Preparing => 'En préparation',
            OrderStatus::Shipped => 'Expédiée',
            OrderStatus::OutForDelivery => 'En cours de livraison',
            OrderStatus::Delivered => 'Livrée',
        };
    }

    /**
     * Statut immédiatement suivant dans le cycle de vie,
     * ou null si le statut est final (Delivered).
     */
    public function next(): ?OrderStatus
    {
        return match ($this) {
            OrderStatus::Created => OrderStatus::Preparing,
            OrderStatus::Preparing => OrderStatus::Shipped,
            OrderStatus::Shipped => OrderStatus::OutForDelivery,
            OrderStatus::OutForDelivery => OrderStatus::Delivered,
            OrderStatus::Delivered => null,
        };
    }

    /**
     * Une commande ne peut avancer que vers son statut immédiatement suivant
     * (pas de saut d'étape, pas de retour en arrière).
     */
    public function canTransitionTo(OrderStatus $target): bool
    {
        return $this->next() === $target;
    }
}
