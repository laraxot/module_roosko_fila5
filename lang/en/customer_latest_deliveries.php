<?php

declare(strict_types=1);

return [
    'table' => ['heading' => 'Latest deliveries'],
    'fields' => [
        'code' => ['label' => 'Code'],
        'arrived_at' => ['label' => 'Arrival'],
        'fir' => [
            'number' => ['label' => 'Waste form'],
            'producerSite' => ['name' => ['label' => 'Site']],
        ],
        'accepted_kg' => ['label' => 'Accepted (kg)'],
        'status' => ['label' => 'Status'],
    ],
];
