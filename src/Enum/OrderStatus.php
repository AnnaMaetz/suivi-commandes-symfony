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
}
