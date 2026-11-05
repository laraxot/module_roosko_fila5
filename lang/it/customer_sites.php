<?php

declare(strict_types=1);

return [
    'fields' => [
        'name' => ['label' => 'Cantiere'],
        'rentri_site_number' => ['label' => 'N. iscrizione RENTRI'],
        'address' => ['full_address' => ['label' => 'Indirizzo']],
        'is_active' => ['label' => 'Attivo'],
        'delivered_kg' => ['label' => 'Conferiti (kg)'],
    ],
    'actions' => [
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il cantiere'],
        'edit' => ['label' => 'Modifica', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Modifica il cantiere'],
    ],
];
