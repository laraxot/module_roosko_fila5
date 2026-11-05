<?php

declare(strict_types=1);

return [
    'table' => ['heading' => 'Giacenza per codice EER'],
    'fields' => [
        'warehouse' => ['label' => 'Magazzino'],
        'ewc_code' => ['label' => 'EER'],
        'description' => ['label' => 'Descrizione'],
        'is_hazardous' => ['label' => 'Pericoloso'],
        'units_count' => ['label' => 'Unità'],
        'stock_kg' => ['label' => 'Giacenza (kg)'],
        'authorized_max_kg' => ['label' => 'Limite autorizzato (kg)'],
        'usage_pct' => ['label' => 'Uso'],
    ],
];
