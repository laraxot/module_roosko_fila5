<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Shipments',
        'plural' => 'Shipments',
        'group' => ['name' => 'Plant'],
        'label' => 'Shipments',
        'icon' => 'heroicon-o-truck',
        'sort' => 30,
    ],
    'label' => 'Shipment',
    'plural_label' => 'Shipments',
    'fields' => [
        'code' => ['label' => 'Code'],
        'status' => ['label' => 'Status'],
    ],
    'actions' => [
        'create_shipment' => ['label' => 'New shipment', 'icon' => 'heroicon-o-plus', 'tooltip' => 'Create a shipment to a recipient'],
        'dispatch' => ['label' => 'Dispatch', 'icon' => 'heroicon-o-paper-airplane', 'tooltip' => 'Dispatch: FIR, register unloads, units out'],
        'cancel' => ['label' => 'Cancel shipment', 'icon' => 'heroicon-o-x-circle', 'tooltip' => 'Cancel the shipment and release the loaded units'],
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the shipment'],
        'edit' => ['label' => 'Edit', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Document and operation'],
        'back' => ['label' => 'Back', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Back to the list'],
    ],
];
