<?php

declare(strict_types=1);

return [
    'sections' => [
        'site' => ['label' => 'Cantiere', 'heading' => 'Cantiere'],
        'deliveries' => ['label' => 'Conferimenti', 'heading' => 'Conferimenti'],
    ],
    'fields' => [
        'name' => ['label' => 'Nome'],
        'operator' => ['business_name' => ['label' => 'Impresa']],
        'rentri_site_number' => ['label' => 'N. iscrizione RENTRI'],
        'is_active' => ['label' => 'Attivo'],
        'address' => ['full_address' => ['label' => 'Indirizzo']],
        'delivered_kg' => ['label' => 'Totale accettato'],
    ],
];
