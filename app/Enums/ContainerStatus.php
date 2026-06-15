<?php

namespace App\Enums;

final class ContainerStatus
{
    public const CONTAINER_PREPARED = 'container_prepared';
    public const LABEL_GENERATED = 'label_generated';
    public const SHIPPED_TO_HOME = 'shipped_to_home';
    public const DELIVERED_TO_HOME = 'delivered_to_home';
    public const CUSTOMER_PACKING = 'customer_packing';
    public const PICKUP_SCHEDULED = 'pickup_scheduled';
    public const RETURN_SHIPMENT_IN_TRANSIT = 'return_shipment_in_transit';
    public const RECEIVED_AT_NEW_LIFE_HUB = 'received_at_new_life_hub';
    public const STORED_AT_RECEIVING_HUB = 'stored_at_receiving_hub';
    public const SCHEDULED_FOR_DORM_DELIVERY = 'scheduled_for_dorm_delivery';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERED_TO_DORM = 'delivered_to_dorm';

    /**
     * @return list<string>
     */
    public static function ordered(): array
    {
        return [
            self::CONTAINER_PREPARED,
            self::LABEL_GENERATED,
            self::SHIPPED_TO_HOME,
            self::DELIVERED_TO_HOME,
            self::CUSTOMER_PACKING,
            self::PICKUP_SCHEDULED,
            self::RETURN_SHIPMENT_IN_TRANSIT,
            self::RECEIVED_AT_NEW_LIFE_HUB,
            self::STORED_AT_RECEIVING_HUB,
            self::SCHEDULED_FOR_DORM_DELIVERY,
            self::OUT_FOR_DELIVERY,
            self::DELIVERED_TO_DORM,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::CONTAINER_PREPARED => 'Container Prepared',
            self::LABEL_GENERATED => 'Label Generated',
            self::SHIPPED_TO_HOME => 'Shipped to Home',
            self::DELIVERED_TO_HOME => 'Delivered to Home',
            self::CUSTOMER_PACKING => 'Student Packing',
            self::PICKUP_SCHEDULED => 'Pickup Scheduled',
            self::RETURN_SHIPMENT_IN_TRANSIT => 'Return Shipment In Transit',
            self::RECEIVED_AT_NEW_LIFE_HUB => 'Received at New Life Hub',
            self::STORED_AT_RECEIVING_HUB => 'Stored at Receiving Hub',
            self::SCHEDULED_FOR_DORM_DELIVERY => 'Scheduled for Dorm Delivery',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED_TO_DORM => 'Delivered to Dorm',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public static function isValid(?string $status): bool
    {
        return $status !== null && in_array($status, self::ordered(), true);
    }

    public static function orderIndex(string $status): int
    {
        $index = array_search($status, self::ordered(), true);

        return $index === false ? 0 : (int) $index;
    }
}
