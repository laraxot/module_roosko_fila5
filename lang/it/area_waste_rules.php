<?php

declare(strict_types=1);

return [
    'fields' => [
        'area' => ['code' => ['label' => 'Area']],
        'ewc_code' => ['label' => 'EER'],
        'operation' => ['label' => 'Operazione'],
        'max_storage_kg' => ['label' => 'Limite area (kg)'],
        'authorized_max_storage_kg' => ['label' => 'Limite autorizzazione (kg)'],
        'notes' => ['label' => 'Note'],
    ],
    'actions' => [
        'edit' => ['label' => 'Modifica', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Modifica la regola'],
        'delete' => ['label' => 'Elimina', 'icon' => 'heroicon-o-trash', 'tooltip' => 'Elimina la regola'],
    ],
];
