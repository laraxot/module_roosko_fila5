<?php

declare(strict_types=1);

return [
    'table' => ['heading' => 'Ultimi conferimenti'],
    'fields' => [
        'code' => ['label' => 'Codice'],
        'arrived_at' => ['label' => 'Arrivo'],
        'fir' => [
            'number' => ['label' => 'FIR'],
            'producerSite' => ['name' => ['label' => 'Cantiere']],
        ],
        'accepted_kg' => ['label' => 'Accettati (kg)'],
        'status' => ['label' => 'Stato'],
    ],
];
