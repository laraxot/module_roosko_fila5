<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Cantieri',
        'group' => 'I miei rifiuti',
        'icon' => 'heroicon-o-building-office-2',
        'sort' => 10,
    ],
    'label' => 'Cantiere',
    'plural_label' => 'Cantieri',
    'plural' => ['label' => 'Cantieri'],
    'fields' => [
        'name' => ['label' => 'Cantiere'],
        'rentri_site_number' => ['label' => 'N. iscrizione RENTRI'],
        'is_active' => ['label' => 'Attivo'],
        'delivered_kg' => ['label' => 'Conferiti (kg)'],
    ],
    'actions' => [
        'create' => ['label' => 'Nuovo cantiere', 'icon' => 'heroicon-o-plus', 'tooltip' => 'Aggiungi un cantiere'],
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il cantiere'],
        'edit' => ['label' => 'Modifica', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Modifica il cantiere'],
        'back' => ['label' => 'Indietro', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Torna all\'elenco'],
    ],
    'errors' => [
        'no_operator' => 'Il tuo account cliente non è ancora collegato a un\'impresa: contatta l\'impianto.',
        'operator_required' => 'Scegli l\'impresa a cui intestare il cantiere.',
    ],
];
