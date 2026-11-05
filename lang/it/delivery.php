<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Conferimenti',
        'plural' => 'Conferimenti',
        'group' => ['name' => 'Impianto'],
        'label' => 'Conferimenti',
        'icon' => 'heroicon-o-arrow-down-tray',
        'sort' => 10,
    ],
    'label' => 'Conferimento',
    'plural_label' => 'Conferimenti',
    'fields' => [
        'code' => ['label' => 'Codice'],
        'status' => ['label' => 'Stato'],
        'arrived_at' => ['label' => 'Arrivo'],
        'fir_number' => ['label' => 'FIR'],
        'ewc_code' => ['label' => 'Codice EER'],
        'accepted_kg' => ['label' => 'Accettati (kg)'],
        'rejected_kg' => ['label' => 'Respinti (kg)'],
        'analysis_required' => ['label' => 'Analisi richiesta'],
    ],
    'actions' => [
        'receive' => ['label' => 'Registra arrivo', 'icon' => 'heroicon-o-arrow-down-tray', 'tooltip' => 'Registra un carico in arrivo con FIR'],
        'weigh_tare' => ['label' => 'Pesa tara', 'icon' => 'heroicon-o-scale', 'tooltip' => 'Seconda pesata a scarico avvenuto'],
        'accept' => ['label' => 'Accetta', 'icon' => 'heroicon-o-check-badge', 'tooltip' => 'Accetta il carico e crea le unità'],
        'reject' => ['label' => 'Respingi', 'icon' => 'heroicon-o-x-circle', 'tooltip' => 'Respingi il carico'],
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il conferimento'],
        'edit' => ['label' => 'Ispezione', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Note di ispezione e analisi'],
        'back' => ['label' => 'Indietro', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Torna all\'elenco'],
    ],
];
