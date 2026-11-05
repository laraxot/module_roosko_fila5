<?php

declare(strict_types=1);

return [
    'sections' => [
        'lot' => ['label' => 'Lot', 'heading' => 'Lot'],
        'unit' => ['label' => 'Unit', 'heading' => 'Stock unit'],
        'traceability' => ['label' => 'Traceability', 'heading' => 'Upstream traceability'],
    ],
    'fields' => [
        'ewc_code' => ['label' => 'EWC code'],
        'physical_state' => ['label' => 'Physical state'],
        'hazard_properties' => ['label' => 'Hazard properties'],
        'is_hazardous' => ['label' => 'Hazardous'],
        'classification' => ['label' => 'Classification'],
        'destination' => ['label' => 'Destination'],
        'eow_batch_code' => ['label' => 'EoW batch'],
        'eow_declaration_number' => ['label' => 'Declaration of conformity'],
        'notes' => ['label' => 'Notes'],
        'stockUnit' => [
            'code' => ['label' => 'Unit code'],
            'status' => ['label' => 'Status'],
            'container_type' => ['label' => 'Container'],
            'net_weight_kg' => ['label' => 'Net weight'],
            'location' => ['code' => ['label' => 'Location']],
            'received_at' => ['label' => 'Received at'],
        ],
        'origin' => ['label' => 'Origin (unit · EWC · delivery · FIR · share)'],
    ],
];
