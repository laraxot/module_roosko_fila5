<?php

declare(strict_types=1);

return [
    'sections' => [
        'delivery' => ['label' => 'Delivery', 'heading' => 'Delivery'],
        'weighing' => ['label' => 'Weighing', 'heading' => 'Weighing'],
        'acceptance' => ['label' => 'Outcome', 'heading' => 'Outcome'],
    ],
    'fields' => [
        'code' => ['label' => 'Code'],
        'status' => ['label' => 'Status'],
        'arrived_at' => ['label' => 'Arrival at the plant'],
        'fir' => [
            'number' => ['label' => 'Waste form number'],
            'producerSite' => ['name' => ['label' => 'Site']],
            'ewcCode' => ['display_code' => ['label' => 'EWC code']],
            'quantity' => ['label' => 'Quantity declared on the form'],
        ],
        'gross_kg' => ['label' => 'Gross'],
        'tare_kg' => ['label' => 'Tare'],
        'net_kg' => ['label' => 'Net'],
        'accepted_kg' => ['label' => 'Accepted'],
        'rejected_kg' => ['label' => 'Rejected'],
        'rejection_reason' => ['label' => 'Rejection reason'],
    ],
];
