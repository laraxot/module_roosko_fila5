<?php

declare(strict_types=1);

return [
    'sections' => [
        'shipment' => ['label' => 'Shipment', 'heading' => 'Shipment'],
    ],
    'fields' => [
        'code' => ['label' => 'Code'],
        'status' => ['label' => 'Status'],
        'document_type' => ['label' => 'Document'],
        'document_number' => ['label' => 'Document number'],
        'recipient' => ['business_name' => ['label' => 'Recipient']],
        'recipientSite' => ['name' => ['label' => 'Destination site']],
        'operation' => ['label' => 'Operation'],
        'fir_number' => ['label' => 'FIR'],
        'total_kg' => ['label' => 'Total'],
        'dispatched_at' => ['label' => 'Dispatched at'],
        'notes' => ['label' => 'Notes'],
    ],
];
