<?php

declare(strict_types=1);

return [
    'sections' => [
        'identification' => ['label' => 'Waste form', 'heading' => 'Waste form'],
        'subjects' => ['label' => 'Parties', 'heading' => 'Parties'],
        'waste' => ['label' => 'Waste', 'heading' => 'Waste'],
        'acceptance_section' => ['label' => 'Transport and acceptance', 'heading' => 'Transport and acceptance'],
    ],
    'fields' => [
        'number' => ['label' => 'Number'],
        'status' => ['label' => 'Status'],
        'is_digital' => ['label' => 'Digital (xFIR)'],
        'producer' => ['business_name' => ['label' => 'Producer']],
        'producerSite' => ['name' => ['label' => 'Site']],
        'transporter' => ['business_name' => ['label' => 'Carrier']],
        'recipient' => ['business_name' => ['label' => 'Recipient']],
        'ewcCode' => [
            'display_code' => ['label' => 'EWC code'],
            'description' => ['label' => 'EWC description'],
        ],
        'operation' => ['label' => 'Operation'],
        'quantity' => ['label' => 'Quantity'],
        'quantity_unit' => ['label' => 'Unit'],
        'is_quantity_estimated' => ['label' => 'Estimated weight'],
        'transport_started_at' => ['label' => 'Transport start'],
        'arrived_at' => ['label' => 'Arrival at the plant'],
        'acceptance' => ['label' => 'Acceptance'],
        'accepted_quantity_kg' => ['label' => 'Accepted quantity'],
        'rejection_reason' => ['label' => 'Rejection reason'],
    ],
];
