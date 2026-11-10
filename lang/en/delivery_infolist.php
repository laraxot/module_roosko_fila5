<?php

declare(strict_types=1);

return [
    'sections' => [
        'delivery' => ['label' => 'Delivery', 'heading' => 'Delivery'],
        'weighing' => ['label' => 'Weighing', 'heading' => 'Weighing'],
        'acceptance' => ['label' => 'Acceptance', 'heading' => 'Acceptance'],
    ],
    'fields' => [
        'code' => ['label' => 'Code'],
        'status' => ['label' => 'Status'],
        'arrived_at' => ['label' => 'Arrived'],
        'analysis_required' => ['label' => 'Analysis required'],
        'fir_number' => ['label' => 'FIR number'],
        'ewc_code' => ['label' => 'EWC code'],
        'plantSite' => ['name' => ['label' => 'Plant']],
        'warehouse' => ['name' => ['label' => 'Warehouse']],
        'gross_kg' => ['label' => 'Gross'],
        'tare_kg' => ['label' => 'Tare'],
        'net_kg' => ['label' => 'Net'],
        'fir_quantity' => ['label' => 'Quantity declared on the FIR', 'estimated' => 'estimated'],
        'accepted_kg' => ['label' => 'Accepted'],
        'rejected_kg' => ['label' => 'Rejected'],
        'rejection_reason' => ['label' => 'Rejection reason'],
        'inspection_notes' => ['label' => 'Inspection notes'],
    ],
];
