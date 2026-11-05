<?php

declare(strict_types=1);

return [
    'fields' => [
        'name' => ['label' => 'Site'],
        'rentri_site_number' => ['label' => 'RENTRI registration no.'],
        'address' => ['full_address' => ['label' => 'Address']],
        'is_active' => ['label' => 'Active'],
        'delivered_kg' => ['label' => 'Delivered (kg)'],
    ],
    'actions' => [
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the site'],
        'edit' => ['label' => 'Edit', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Edit the site'],
    ],
];
