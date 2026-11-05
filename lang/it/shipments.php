<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Codice'],
        'recipient' => ['business_name' => ['label' => 'Destinatario']],
        'document_type' => ['label' => 'Documento'],
        'document_number' => ['label' => 'Numero'],
        'status' => ['label' => 'Stato'],
        'items_count' => ['label' => 'Unità'],
        'total_kg' => ['label' => 'Totale (kg)'],
        'dispatched_at' => ['label' => 'Spedita il'],
    ],
    'actions' => [
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri l\'uscita'],
    ],
];
