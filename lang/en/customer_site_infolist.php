<?php

declare(strict_types=1);

return [
    'sections' => [
        'site' => ['label' => 'Site', 'heading' => 'Site'],
        'deliveries' => ['label' => 'Deliveries', 'heading' => 'Deliveries'],
    ],
    'fields' => [
        'name' => ['label' => 'Name'],
        'operator' => ['business_name' => ['label' => 'Company']],
        'rentri_site_number' => ['label' => 'RENTRI registration no.'],
        'is_active' => ['label' => 'Active'],
        'address' => ['full_address' => ['label' => 'Address']],
        'delivered_kg' => ['label' => 'Total accepted'],
    ],
];
