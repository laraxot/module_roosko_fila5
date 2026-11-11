<?php

declare(strict_types=1);

return [
    'sections' => [
        'shipment' => ['label' => 'Uscita', 'heading' => 'Uscita'],
    ],
    'fields' => [
        'code' => ['label' => 'Codice'],
        'status' => ['label' => 'Stato'],
        'document_type' => ['label' => 'Documento'],
        'document_number' => ['label' => 'Numero documento'],
        'recipient' => ['business_name' => ['label' => 'Destinatario']],
        'recipientSite' => ['name' => ['label' => 'Sede di destinazione']],
        'operation' => ['label' => 'Operazione'],
        'fir_number' => [
            'label' => 'FIR',
            'draft_help' => 'FIR in bozza: completa trasportatore, targa e conducente nel pannello Rifiuti, poi «Cambia stato» → Emesso. Il FIR digitale lo emette RENTRI.',
        ],
        'total_kg' => ['label' => 'Totale'],
        'dispatched_at' => ['label' => 'Spedita il'],
        'notes' => ['label' => 'Note'],
    ],
];
