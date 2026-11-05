<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Register product of treatment :code',
    'modal_description' => 'Each product becomes a unit with a QR code and a label. If it is still a waste, a new-production load is written to the register; End of Waste and by-products are not.',
    'success' => 'Unit :code registered on treatment :order',
    'error' => 'Product not registered',
    'fields' => [
        'ewc_code_id' => ['label' => 'EWC code'],
        'physical_state' => ['label' => 'Physical state'],
        'hazard_properties' => ['label' => 'Hazard properties (HP)', 'helper_text' => 'Required for a hazardous EWC code'],
        'classification' => ['label' => 'Classification'],
        'destination' => ['label' => 'Destination'],
        'eow_batch_code' => ['label' => 'End of Waste batch code', 'helper_text' => 'EoW production batch code'],
        'kind' => ['label' => 'Output kind'],
        'container_type' => ['label' => 'Container'],
        'net_weight_kg' => ['label' => 'Net weight'],
        'tare_weight_kg' => ['label' => 'Container tare'],
        'location_id' => ['label' => 'Location', 'helper_text' => 'Optional: can be stored later by scanning'],
    ],
];
