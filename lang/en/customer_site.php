<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Sites',
        'group' => 'My waste',
        'icon' => 'heroicon-o-building-office-2',
        'sort' => 10,
    ],
    'label' => 'Site',
    'plural_label' => 'Sites',
    'plural' => ['label' => 'Sites'],
    'fields' => [
        'name' => ['label' => 'Site'],
        'rentri_site_number' => ['label' => 'RENTRI registration no.'],
        'is_active' => ['label' => 'Active'],
        'delivered_kg' => ['label' => 'Delivered (kg)'],
    ],
    'actions' => [
        'create' => ['label' => 'New site', 'icon' => 'heroicon-o-plus', 'tooltip' => 'Add a site'],
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the site'],
        'edit' => ['label' => 'Edit', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Edit the site'],
        'back' => ['label' => 'Back', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Back to the list'],
    ],
    'errors' => [
        'no_operator' => 'Your customer account is not linked to a company yet: please contact the plant.',
        'operator_required' => 'Choose the company the site belongs to.',
    ],
];
