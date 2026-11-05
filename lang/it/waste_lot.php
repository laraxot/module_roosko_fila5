<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Lotti',
        'plural' => 'Lotti',
        'group' => ['name' => 'Impianto'],
        'label' => 'Lotti',
        'icon' => 'heroicon-o-cube',
        'sort' => 20,
    ],
    'label' => 'Lotto',
    'plural_label' => 'Lotti',
    'fields' => [
        'unit_code' => ['label' => 'Unità'],
        'unit_status' => ['label' => 'Stato unità'],
        'net_weight_kg' => ['label' => 'Peso netto (kg)'],
        'location' => ['label' => 'Posizione'],
        'classification' => ['label' => 'Classificazione'],
        'destination' => ['label' => 'Destino'],
    ],
    'actions' => [
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il lotto'],
        'edit' => ['label' => 'Modifica', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Destino e riferimenti EoW'],
        'back' => ['label' => 'Indietro', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Torna all\'elenco'],
    ],
];
