<?php

declare(strict_types=1);

return [
    'name' => 'Roosko',
    'description' => 'Gestione impianto di trattamento e cernita rifiuti',
    'icon' => 'heroicon-o-truck',
    'navigation' => [
        'enabled' => true,
        'sort' => 10,
    ],

    /*
     * Code su cui accodare il lavoro asincrono (null = coda di default del connettore).
     */
    'queues' => [
        'labels' => null,
        'rentri' => null,
    ],

    /*
     * Soglie (% del limite istantaneo autorizzato) per il colore delle stat di
     * AuthorizationUsageWidget: sotto `warning` verde, poi giallo, da `danger` rosso.
     */
    'authorization_usage_thresholds' => [
        'warning' => 80.0,
        'danger' => 95.0,
    ],
];
