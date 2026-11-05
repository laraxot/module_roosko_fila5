<?php

declare(strict_types=1);

return [
    'sections' => [
        'delivery' => ['label' => 'Conferimento', 'heading' => 'Conferimento'],
        'weighing' => ['label' => 'Pesata', 'heading' => 'Pesata'],
        'acceptance' => ['label' => 'Esito', 'heading' => 'Esito'],
    ],
    'fields' => [
        'code' => ['label' => 'Codice'],
        'status' => ['label' => 'Stato'],
        'arrived_at' => ['label' => 'Arrivo in impianto'],
        'fir' => [
            'number' => ['label' => 'Numero FIR'],
            'producerSite' => ['name' => ['label' => 'Cantiere']],
            'ewcCode' => ['display_code' => ['label' => 'Codice EER']],
            'quantity' => ['label' => 'Quantità dichiarata sul FIR'],
        ],
        'gross_kg' => ['label' => 'Lordo'],
        'tare_kg' => ['label' => 'Tara'],
        'net_kg' => ['label' => 'Netto'],
        'accepted_kg' => ['label' => 'Accettati'],
        'rejected_kg' => ['label' => 'Respinti'],
        'rejection_reason' => ['label' => 'Motivo del respingimento'],
    ],
];
