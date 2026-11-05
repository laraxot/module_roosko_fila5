<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Uscite',
        'plural' => 'Uscite',
        'group' => ['name' => 'Impianto'],
        'label' => 'Uscite',
        'icon' => 'heroicon-o-truck',
        'sort' => 30,
    ],
    'label' => 'Uscita',
    'plural_label' => 'Uscite',
    'fields' => [
        'code' => ['label' => 'Codice'],
        'status' => ['label' => 'Stato'],
    ],
    'actions' => [
        'create_shipment' => ['label' => 'Nuova uscita', 'icon' => 'heroicon-o-plus', 'tooltip' => 'Crea un\'uscita verso un destinatario'],
        'dispatch' => ['label' => 'Spedisci', 'icon' => 'heroicon-o-paper-airplane', 'tooltip' => 'Spedisci: FIR, scarichi di registro, unità in uscita'],
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri l\'uscita'],
        'edit' => ['label' => 'Modifica', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Documento e operazione'],
        'back' => ['label' => 'Indietro', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Torna all\'elenco'],
    ],
];
