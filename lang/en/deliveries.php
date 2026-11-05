<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Code'],
        'arrived_at' => ['label' => 'Arrived'],
        'fir_number' => ['label' => 'FIR'],
        'status' => ['label' => 'Status'],
        'accepted_kg' => ['label' => 'Accepted (kg)'],
        'rejected_kg' => ['label' => 'Rejected (kg)'],
        'analysis_required' => ['label' => 'Analysis'],
    ],
    'actions' => [
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the delivery'],
    ],
];
