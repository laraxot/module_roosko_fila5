<?php

declare(strict_types=1);

return [
    'title' => 'Operator',
    'navigation' => [
        'label' => 'Operator (scan)',
        'group' => 'Yard',
        'icon' => 'heroicon-o-qr-code',
        'sort' => 1,
    ],
    'scan' => [
        'field' => 'Scanned code',
        'placeholder' => 'Scan a unit or a location',
        'placeholder_location' => 'Scan the destination location',
        'hint' => 'Use the handheld trigger or the camera. The field is always ready.',
        'camera' => 'Camera',
        'camera_close' => 'Close',
        'not_found' => 'Code “:code” not recognized',
        'location_expected' => 'A location scan is required',
        'awaiting_location' => 'Unit :code: scan the location',
    ],
    'unit' => [
        'ewc' => 'EWC',
        'weight' => 'Net weight',
        'location' => 'Location',
        'status' => 'Status',
        'classification' => 'Classification',
        'destination' => 'Destination',
    ],
    'location' => [
        'units' => 'Units here',
        'empty' => 'No units in this location',
    ],
    'actions' => [
        'store' => 'Store',
        'move' => 'Move',
        'cancel' => 'Cancel',
        'clear' => 'New scan',
        'startTreatment' => ['label' => 'Start treatment', 'icon' => 'heroicon-o-cog-6-tooth', 'tooltip' => 'Start a treatment with this unit'],
        'addToShipment' => ['label' => 'Add to shipment', 'icon' => 'heroicon-o-truck', 'tooltip' => 'Load the unit onto a shipment'],
        'reprintLabel' => ['label' => 'Reprint label', 'icon' => 'heroicon-o-printer', 'tooltip' => 'Reprint the QR label'],
        'adjustWeight' => ['label' => 'Adjust weight', 'icon' => 'heroicon-o-scale', 'tooltip' => 'Correct the net weight with a reason'],
    ],
    'fields' => [
        'machine_id' => ['label' => 'Machine'],
        'operation' => ['label' => 'Operation'],
        'shipment_id' => ['label' => 'Shipment'],
        'copies' => ['label' => 'Copies'],
        'new_net_kg' => ['label' => 'New net weight'],
        'reason' => ['label' => 'Reason'],
    ],
    'notifications' => [
        'error' => 'Operation not performed',
        'stored' => 'Unit :code in :location',
        'treatment_started' => 'Treatment started with :code',
        'added_to_shipment' => 'Unit :code loaded onto :shipment',
        'label_queued' => 'Label for :code queued for printing',
        'weight_adjusted' => 'Weight of :code adjusted',
    ],
];
