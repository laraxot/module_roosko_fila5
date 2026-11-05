<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Accept delivery :code',
    'modal_description' => 'Split the accepted load into units (containers): the sum of net weights must match the accepted kg. Each unit gets a QR code and a printed label.',
    'success' => 'Delivery :code accepted',
    'error' => 'Acceptance not recorded',
    'fields' => [
        'accepted_kg' => ['label' => 'Accepted kg'],
        'rejected_kg' => ['label' => 'Rejected kg'],
        'operation' => ['label' => 'Operation', 'helper_text' => 'E.g. R13 storage'],
        'rejection_reason' => ['label' => 'Partial rejection reason'],
        'units' => ['label' => 'Units'],
        'container_type' => ['label' => 'Container'],
        'net_weight_kg' => ['label' => 'Net weight'],
        'tare_weight_kg' => ['label' => 'Container tare'],
        'location_id' => ['label' => 'Location', 'helper_text' => 'Optional: can be stored later by scanning'],
    ],
];
