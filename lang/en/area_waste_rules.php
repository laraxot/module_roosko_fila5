<?php

declare(strict_types=1);

return [
    'fields' => [
        'area' => ['code' => ['label' => 'Area']],
        'ewc_code' => ['label' => 'EWC'],
        'operation' => ['label' => 'Operation'],
        'max_storage_kg' => ['label' => 'Area limit (kg)'],
        'authorized_max_storage_kg' => ['label' => 'Authorization limit (kg)'],
        'notes' => ['label' => 'Notes'],
    ],
    'actions' => [
        'edit' => ['label' => 'Edit', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Edit the rule'],
        'delete' => ['label' => 'Delete', 'icon' => 'heroicon-o-trash', 'tooltip' => 'Delete the rule'],
    ],
];
