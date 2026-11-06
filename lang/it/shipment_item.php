<?php

declare(strict_types=1);

return [
    'label' => 'Unità caricata',
    'plural_label' => 'Unità caricate',
    'fields' => [
        'stockUnit' => [
            'code' => ['label' => 'Unità'],
            'status' => ['label' => 'Stato unità'],
        ],
        'ewc_code' => ['label' => 'EER'],
        'quantity_kg' => ['label' => 'Peso (kg)'],
    ],
    'actions' => [
        'load' => ['label' => 'Carica unità', 'icon' => 'heroicon-o-qr-code', 'tooltip' => 'Scansiona o digita il codice dell\'unità'],
        'remove' => ['label' => 'Togli', 'icon' => 'heroicon-o-trash', 'tooltip' => 'Togli l\'unità dall\'uscita e liberala'],
        'delete' => ['label' => 'Scarica', 'icon' => 'heroicon-o-trash', 'tooltip' => 'Togli l\'unità dall\'uscita'],
    ],
];
