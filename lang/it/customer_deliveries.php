<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Codice'],
        'arrived_at' => ['label' => 'Arrivo'],
        'fir' => [
            'number' => ['label' => 'FIR'],
            'producerSite' => ['name' => ['label' => 'Cantiere']],
            'ewcCode' => ['display_code' => ['label' => 'EER']],
        ],
        'accepted_kg' => ['label' => 'Accettati (kg)'],
        'status' => ['label' => 'Stato'],
    ],
    'actions' => [
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il conferimento'],
    ],
];
