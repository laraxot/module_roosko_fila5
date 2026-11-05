<?php

declare(strict_types=1);

return [
    'fields' => [
        'unit_code' => ['label' => 'Unit'],
        'ewc_code' => ['label' => 'EWC'],
        'classification' => ['label' => 'Classification'],
        'destination' => ['label' => 'Destination'],
        'unit_status' => ['label' => 'Unit status'],
        'net_weight_kg' => ['label' => 'Net (kg)'],
        'eow_batch_code' => ['label' => 'EoW batch'],
        'created_at' => ['label' => 'Created at'],
    ],
    'actions' => [
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the lot'],
        'edit' => ['label' => 'Edit', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Edit the lot'],
    ],
];
