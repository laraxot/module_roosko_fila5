<?php

declare(strict_types=1);

return [
    'modal_heading' => 'New shipment',
    'success' => 'Shipment created',
    'error' => 'Shipment not created',
    'fields' => [
        'warehouse_id' => ['label' => 'Warehouse'],
        'recipient_operator_id' => ['label' => 'Recipient'],
        'recipient_site_id' => ['label' => 'Destination site'],
        'document_type' => ['label' => 'Document'],
        'operation' => ['label' => 'Destination operation', 'helper_text' => 'R/D code at the destination plant'],
        'document_number' => ['label' => 'Document number', 'helper_text' => 'Delivery note or declaration of conformity. Leave empty for a FIR: it is created as a draft in the Waste panel on dispatch, where number and carrier are filled in (a digital FIR is numbered by RENTRI)'],
        'notes' => ['label' => 'Notes'],
    ],
];
