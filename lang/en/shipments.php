<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Code'],
        'recipient' => ['business_name' => ['label' => 'Recipient']],
        'document_type' => ['label' => 'Document'],
        'document_number' => ['label' => 'Number'],
        'status' => ['label' => 'Status'],
        'items_count' => ['label' => 'Units'],
        'total_kg' => ['label' => 'Total (kg)'],
        'dispatched_at' => ['label' => 'Dispatched at'],
    ],
    'actions' => [
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the shipment'],
    ],
];
