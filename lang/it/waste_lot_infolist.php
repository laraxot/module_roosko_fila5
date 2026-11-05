<?php

declare(strict_types=1);

return [
    'sections' => [
        'lot' => ['label' => 'Lotto', 'heading' => 'Lotto'],
        'unit' => ['label' => 'Unità', 'heading' => 'Unità di stoccaggio'],
        'traceability' => ['label' => 'Tracciabilità', 'heading' => 'Tracciabilità a monte'],
    ],
    'fields' => [
        'ewc_code' => ['label' => 'Codice EER'],
        'physical_state' => ['label' => 'Stato fisico'],
        'hazard_properties' => ['label' => 'Caratteristiche di pericolo'],
        'is_hazardous' => ['label' => 'Pericoloso'],
        'classification' => ['label' => 'Classificazione'],
        'destination' => ['label' => 'Destino'],
        'eow_batch_code' => ['label' => 'Lotto EoW'],
        'eow_declaration_number' => ['label' => 'Dichiarazione di conformità'],
        'notes' => ['label' => 'Note'],
        'stockUnit' => [
            'code' => ['label' => 'Codice unità'],
            'status' => ['label' => 'Stato'],
            'container_type' => ['label' => 'Contenitore'],
            'net_weight_kg' => ['label' => 'Peso netto'],
            'location' => ['code' => ['label' => 'Posizione']],
            'received_at' => ['label' => 'Ricevuta il'],
        ],
        'origin' => ['label' => 'Origine (unità · EER · conferimento · FIR · quota)'],
    ],
];
