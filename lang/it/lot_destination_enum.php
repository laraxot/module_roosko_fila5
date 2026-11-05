<?php

declare(strict_types=1);

return [
    'label' => 'Destino',
    'values' => [
        'internal_treatment' => ['label' => 'Trattamento interno', 'color' => 'info', 'icon' => 'heroicon-o-cog-6-tooth', 'description' => 'Resta in impianto per il trattamento'],
        'sale' => ['label' => 'Vendita', 'color' => 'success', 'icon' => 'heroicon-o-banknotes', 'description' => 'Vendita come End of Waste o sottoprodotto'],
        'external_recovery' => ['label' => 'Recupero presso terzi', 'color' => 'primary', 'icon' => 'heroicon-o-arrow-path', 'description' => 'Avviato a recupero (R) in altro impianto'],
        'disposal' => ['label' => 'Smaltimento', 'color' => 'danger', 'icon' => 'heroicon-o-fire', 'description' => 'Avviato a smaltimento (D)'],
        'remediation' => ['label' => 'Bonifica', 'color' => 'warning', 'icon' => 'heroicon-o-beaker', 'description' => 'Destinato a operazioni di bonifica'],
    ],
];
