<?php

declare(strict_types=1);

return [
    'label' => 'Classificazione',
    'values' => [
        'waste' => ['label' => 'Rifiuto', 'color' => 'warning', 'icon' => 'heroicon-o-trash', 'description' => 'Rifiuto: movimentato con FIR e registro di carico/scarico'],
        'end_of_waste' => ['label' => 'End of Waste', 'color' => 'success', 'icon' => 'heroicon-o-sparkles', 'description' => 'Cessazione della qualifica di rifiuto (art. 184-ter): prodotto vendibile'],
        'by_product' => ['label' => 'Sottoprodotto', 'color' => 'info', 'icon' => 'heroicon-o-cube', 'description' => 'Sottoprodotto (art. 184-bis), non è un rifiuto'],
    ],
];
