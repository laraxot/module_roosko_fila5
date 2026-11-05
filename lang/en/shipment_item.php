<?php

declare(strict_types=1);

return [
    'label' => 'Loaded unit',
    'plural_label' => 'Loaded units',
    'fields' => [
        'stockUnit' => [
            'code' => ['label' => 'Unit'],
            'status' => ['label' => 'Unit status'],
        ],
        'ewc_code' => ['label' => 'EWC'],
        'quantity_kg' => ['label' => 'Weight (kg)'],
    ],
    'actions' => [
        'load' => ['label' => 'Load unit', 'icon' => 'heroicon-o-qr-code', 'tooltip' => 'Scan or type the unit code'],
        'delete' => ['label' => 'Unload', 'icon' => 'heroicon-o-trash', 'tooltip' => 'Remove the unit from the shipment'],
    ],
];
