<?php

declare(strict_types=1);

return [
    'table' => ['heading' => 'Stock by EWC code'],
    'fields' => [
        'warehouse' => ['label' => 'Warehouse'],
        'ewc_code' => ['label' => 'EWC'],
        'description' => ['label' => 'Description'],
        'is_hazardous' => ['label' => 'Hazardous'],
        'units_count' => ['label' => 'Units'],
        'stock_kg' => ['label' => 'Stock (kg)'],
        'authorized_max_kg' => ['label' => 'Authorized limit (kg)'],
        'usage_pct' => ['label' => 'Usage'],
    ],
];
