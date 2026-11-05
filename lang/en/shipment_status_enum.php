<?php

declare(strict_types=1);

return [
    'label' => 'Shipment status',
    'values' => [
        'draft' => ['label' => 'Draft', 'color' => 'gray', 'icon' => 'heroicon-o-pencil-square', 'description' => 'Shipment created, no units loaded'],
        'loading' => ['label' => 'Loading', 'color' => 'info', 'icon' => 'heroicon-o-arrow-up-tray', 'description' => 'Units being loaded'],
        'ready' => ['label' => 'Ready', 'color' => 'warning', 'icon' => 'heroicon-o-clipboard-document-check', 'description' => 'Fully loaded, awaiting departure'],
        'dispatched' => ['label' => 'Dispatched', 'color' => 'success', 'icon' => 'heroicon-o-paper-airplane', 'description' => 'Shipment left the plant'],
        'delivered' => ['label' => 'Delivered', 'color' => 'success', 'icon' => 'heroicon-o-check-circle', 'description' => 'Delivery confirmed at destination'],
        'cancelled' => ['label' => 'Cancelled', 'color' => 'danger', 'icon' => 'heroicon-o-x-circle', 'description' => 'Shipment cancelled before departure'],
    ],
];
