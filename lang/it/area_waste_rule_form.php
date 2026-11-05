<?php

declare(strict_types=1);

return [
    'fields' => [
        'area_id' => ['label' => 'Area', 'helper_text' => 'Magazzino / area in cui il codice può essere stoccato'],
        'authorized_waste_id' => ['label' => 'Codice autorizzato', 'helper_text' => 'Codice EER dell\'autorizzazione d\'impianto'],
        'operation' => ['label' => 'Operazione di stoccaggio'],
        'max_storage_kg' => ['label' => 'Limite istantaneo dell\'area', 'helper_text' => 'Vuoto = solo il limite dell\'autorizzazione'],
        'notes' => ['label' => 'Note'],
    ],
];
