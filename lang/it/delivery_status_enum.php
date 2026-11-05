<?php

declare(strict_types=1);

return [
    'label' => 'Stato conferimento',
    'values' => [
        'arrived' => ['label' => 'Arrivato', 'color' => 'gray', 'icon' => 'heroicon-o-truck', 'description' => 'Carico arrivato in impianto, in attesa di pesata o verifica'],
        'weighed' => ['label' => 'Pesato', 'color' => 'info', 'icon' => 'heroicon-o-scale', 'description' => 'Pesata lordo/tara chiusa'],
        'inspecting' => ['label' => 'In verifica', 'color' => 'warning', 'icon' => 'heroicon-o-magnifying-glass', 'description' => 'Verifica di conformità o analisi in corso'],
        'accepted' => ['label' => 'Accettato', 'color' => 'success', 'icon' => 'heroicon-o-check-badge', 'description' => 'Carico accettato per intero'],
        'partially_accepted' => ['label' => 'Accettato parzialmente', 'color' => 'warning', 'icon' => 'heroicon-o-adjustments-horizontal', 'description' => 'Parte del carico respinta'],
        'rejected' => ['label' => 'Respinto', 'color' => 'danger', 'icon' => 'heroicon-o-x-circle', 'description' => 'Carico respinto al produttore'],
        'closed' => ['label' => 'Chiuso', 'color' => 'gray', 'icon' => 'heroicon-o-lock-closed', 'description' => 'Pratica chiusa'],
    ],
];
