<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Regole aree',
        'plural' => 'Regole aree',
        'group' => ['name' => 'Autorizzazioni'],
        'label' => 'Regole di stoccaggio',
        'icon' => 'heroicon-o-shield-check',
        'sort' => 50,
    ],
    'label' => 'Regola di stoccaggio',
    'plural_label' => 'Regole di stoccaggio',
    'fields' => [
        'area_id' => ['label' => 'Area'],
        'authorized_waste_id' => ['label' => 'Codice autorizzato'],
        'operation' => ['label' => 'Operazione'],
        'max_storage_kg' => ['label' => 'Limite area (kg)'],
        'notes' => ['label' => 'Note'],
    ],
    'actions' => [
        'create' => ['label' => 'Nuova regola', 'icon' => 'heroicon-o-plus', 'tooltip' => 'Autorizza un codice in un\'area'],
        'edit' => ['label' => 'Modifica', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Modifica la regola'],
        'delete' => ['label' => 'Elimina', 'icon' => 'heroicon-o-trash', 'tooltip' => 'Elimina la regola'],
    ],
];
