<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Area rules',
        'plural' => 'Area rules',
        'group' => ['name' => 'Authorizations'],
        'label' => 'Storage rules',
        'icon' => 'heroicon-o-shield-check',
        'sort' => 50,
    ],
    'label' => 'Storage rule',
    'plural_label' => 'Storage rules',
    'fields' => [
        'area_id' => ['label' => 'Area'],
        'authorized_waste_id' => ['label' => 'Authorized code'],
        'operation' => ['label' => 'Operation'],
        'max_storage_kg' => ['label' => 'Area limit (kg)'],
        'notes' => ['label' => 'Notes'],
    ],
    'actions' => [
        'create' => ['label' => 'New rule', 'icon' => 'heroicon-o-plus', 'tooltip' => 'Authorize a code in an area'],
        'edit' => ['label' => 'Edit', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Edit the rule'],
        'delete' => ['label' => 'Delete', 'icon' => 'heroicon-o-trash', 'tooltip' => 'Delete the rule'],
    ],
];
