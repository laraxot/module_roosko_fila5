<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Shipment code'],
        'document_number' => ['label' => 'Document number', 'helper_text' => 'Delivery note or declaration of conformity number (required for End of Waste)'],
        'operation' => ['label' => 'Destination operation', 'helper_text' => 'R/D code at the destination plant (required with a FIR)'],
        'notes' => ['label' => 'Notes'],
    ],
];
