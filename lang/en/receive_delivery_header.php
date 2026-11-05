<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Incoming load',
    'modal_description' => 'Select the FIR and, if the truck is on the weighbridge, record the gross weight.',
    'success' => 'Delivery registered',
    'error' => 'Delivery not registered',
    'fields' => [
        'fir_id' => ['label' => 'FIR', 'placeholder' => 'Search by number'],
        'plant_site_id' => ['label' => 'Plant'],
        'warehouse_id' => ['label' => 'Warehouse'],
        'gross_kg' => ['label' => 'Gross weight', 'helper_text' => 'First inbound weighing; empty when weighing on a platform scale'],
        'scale_id' => ['label' => 'Scale'],
    ],
];
