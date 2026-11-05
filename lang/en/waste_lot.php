<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Lots',
        'plural' => 'Lots',
        'group' => ['name' => 'Plant'],
        'label' => 'Lots',
        'icon' => 'heroicon-o-cube',
        'sort' => 20,
    ],
    'label' => 'Lot',
    'plural_label' => 'Lots',
    'fields' => [
        'unit_code' => ['label' => 'Unit'],
        'unit_status' => ['label' => 'Unit status'],
        'net_weight_kg' => ['label' => 'Net weight (kg)'],
        'location' => ['label' => 'Location'],
        'classification' => ['label' => 'Classification'],
        'destination' => ['label' => 'Destination'],
    ],
    'actions' => [
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the lot'],
        'edit' => ['label' => 'Edit', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Destination and EoW references'],
        'back' => ['label' => 'Back', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Back to the list'],
    ],
];
