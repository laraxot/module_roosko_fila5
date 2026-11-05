<?php

declare(strict_types=1);

return [
    'label' => 'Stato uscita',
    'values' => [
        'draft' => ['label' => 'Bozza', 'color' => 'gray', 'icon' => 'heroicon-o-pencil-square', 'description' => 'Uscita creata, nessuna unità caricata'],
        'loading' => ['label' => 'In carico', 'color' => 'info', 'icon' => 'heroicon-o-arrow-up-tray', 'description' => 'Unità in caricamento'],
        'ready' => ['label' => 'Pronta', 'color' => 'warning', 'icon' => 'heroicon-o-clipboard-document-check', 'description' => 'Carico completo, in attesa di partenza'],
        'dispatched' => ['label' => 'Spedita', 'color' => 'success', 'icon' => 'heroicon-o-paper-airplane', 'description' => 'Uscita partita dall\'impianto'],
        'delivered' => ['label' => 'Consegnata', 'color' => 'success', 'icon' => 'heroicon-o-check-circle', 'description' => 'Consegna confermata a destino'],
        'cancelled' => ['label' => 'Annullata', 'color' => 'danger', 'icon' => 'heroicon-o-x-circle', 'description' => 'Uscita annullata prima della partenza'],
    ],
];
