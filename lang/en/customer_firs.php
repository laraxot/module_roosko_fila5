<?php

declare(strict_types=1);

return [
    'fields' => [
        'number' => ['label' => 'Number'],
        'transport_started_at' => ['label' => 'Date'],
        'producerSite' => ['name' => ['label' => 'Site']],
        'ewcCode' => ['display_code' => ['label' => 'EWC']],
        'quantity' => ['label' => 'Quantity'],
        'quantity_unit' => ['label' => 'Unit'],
        'status' => ['label' => 'Status'],
    ],
    'actions' => [
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the waste form'],
    ],
];
