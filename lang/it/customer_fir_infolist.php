<?php

declare(strict_types=1);

return [
    'sections' => [
        'identification' => ['label' => 'Formulario', 'heading' => 'Formulario'],
        'subjects' => ['label' => 'Soggetti', 'heading' => 'Soggetti'],
        'waste' => ['label' => 'Rifiuto', 'heading' => 'Rifiuto'],
        'acceptance_section' => ['label' => 'Trasporto e accettazione', 'heading' => 'Trasporto e accettazione'],
    ],
    'fields' => [
        'number' => ['label' => 'Numero'],
        'status' => ['label' => 'Stato'],
        'is_digital' => ['label' => 'Digitale (xFIR)'],
        'producer' => ['business_name' => ['label' => 'Produttore']],
        'producerSite' => ['name' => ['label' => 'Cantiere']],
        'transporter' => ['business_name' => ['label' => 'Trasportatore']],
        'recipient' => ['business_name' => ['label' => 'Destinatario']],
        'ewcCode' => [
            'display_code' => ['label' => 'Codice EER'],
            'description' => ['label' => 'Descrizione EER'],
        ],
        'operation' => ['label' => 'Operazione'],
        'quantity' => ['label' => 'Quantità'],
        'quantity_unit' => ['label' => 'Unità'],
        'is_quantity_estimated' => ['label' => 'Peso stimato'],
        'transport_started_at' => ['label' => 'Inizio trasporto'],
        'arrived_at' => ['label' => 'Arrivo in impianto'],
        'acceptance' => ['label' => 'Accettazione'],
        'accepted_quantity_kg' => ['label' => 'Quantità accettata'],
        'rejection_reason' => ['label' => 'Motivo del respingimento'],
    ],
];
