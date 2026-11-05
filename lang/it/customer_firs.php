<?php

declare(strict_types=1);

return [
    'fields' => [
        'number' => ['label' => 'Numero'],
        'transport_started_at' => ['label' => 'Data'],
        'producerSite' => ['name' => ['label' => 'Cantiere']],
        'ewcCode' => ['display_code' => ['label' => 'EER']],
        'quantity' => ['label' => 'Quantità'],
        'quantity_unit' => ['label' => 'Unità'],
        'status' => ['label' => 'Stato'],
    ],
    'actions' => [
        'view' => ['label' => 'Apri', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Apri il formulario'],
    ],
];
