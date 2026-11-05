<?php

declare(strict_types=1);

return [
    'fields' => [
        'unit_code' => ['label' => 'Unità'],
        'ewc_code' => ['label' => 'EER'],
        'classification' => ['label' => 'Classificazione'],
        'destination' => ['label' => 'Destino'],
        'unit_status' => ['label' => 'Stato unità'],
        'net_weight_kg' => ['label' => 'Netto (kg)'],
        'eow_batch_code' => ['label' => 'Lotto EoW'],
        'created_at' => ['label' => 'Creato il'],
    ],
    'actions' => [
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il lotto'],
        'edit' => ['label' => 'Modifica', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Modifica il lotto'],
    ],
];
