<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Codice'],
        'arrived_at' => ['label' => 'Arrivo'],
        'fir_number' => ['label' => 'FIR'],
        'status' => ['label' => 'Stato'],
        'accepted_kg' => ['label' => 'Accettati (kg)'],
        'rejected_kg' => ['label' => 'Respinti (kg)'],
        'analysis_required' => ['label' => 'Analisi'],
    ],
    'actions' => [
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il conferimento'],
    ],
];
