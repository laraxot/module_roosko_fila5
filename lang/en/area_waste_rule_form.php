<?php

declare(strict_types=1);

return [
    'fields' => [
        'area_id' => ['label' => 'Area', 'helper_text' => 'Warehouse / area where the code may be stored'],
        'authorized_waste_id' => ['label' => 'Authorized code', 'helper_text' => 'EWC code of the plant authorization'],
        'operation' => ['label' => 'Storage operation'],
        'max_storage_kg' => ['label' => 'Area instantaneous limit', 'helper_text' => 'Empty = authorization limit only'],
        'notes' => ['label' => 'Notes'],
    ],
];
