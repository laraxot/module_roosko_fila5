<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Code'],
        'arrived_at' => ['label' => 'Arrival'],
        'fir' => [
            'number' => ['label' => 'Waste form'],
            'producerSite' => ['name' => ['label' => 'Site']],
            'ewcCode' => ['display_code' => ['label' => 'EWC']],
        ],
        'accepted_kg' => ['label' => 'Accepted (kg)'],
        'status' => ['label' => 'Status'],
    ],
    'actions' => [
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the delivery'],
    ],
];
