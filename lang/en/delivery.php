<?php

declare(strict_types=1);

return [
    'navigation' => [
        'name' => 'Deliveries',
        'plural' => 'Deliveries',
        'group' => ['name' => 'Plant'],
        'label' => 'Deliveries',
        'icon' => 'heroicon-o-arrow-down-tray',
        'sort' => 10,
    ],
    'label' => 'Delivery',
    'plural_label' => 'Deliveries',
    'fields' => [
        'code' => ['label' => 'Code'],
        'status' => ['label' => 'Status'],
        'arrived_at' => ['label' => 'Arrived'],
        'fir_number' => ['label' => 'FIR'],
        'ewc_code' => ['label' => 'EWC code'],
        'accepted_kg' => ['label' => 'Accepted (kg)'],
        'rejected_kg' => ['label' => 'Rejected (kg)'],
        'analysis_required' => ['label' => 'Analysis required'],
    ],
    'actions' => [
        'receive' => ['label' => 'Register arrival', 'icon' => 'heroicon-o-arrow-down-tray', 'tooltip' => 'Register an incoming load with its FIR'],
        'weigh_tare' => ['label' => 'Weigh tare', 'icon' => 'heroicon-o-scale', 'tooltip' => 'Second weighing after unloading'],
        'accept' => ['label' => 'Accept', 'icon' => 'heroicon-o-check-badge', 'tooltip' => 'Accept the load and create the units'],
        'reject' => ['label' => 'Reject', 'icon' => 'heroicon-o-x-circle', 'tooltip' => 'Reject the load'],
        'view' => ['label' => 'Open', 'icon' => 'heroicon-o-eye', 'tooltip' => 'Open the delivery'],
        'edit' => ['label' => 'Inspection', 'icon' => 'heroicon-o-pencil-square', 'tooltip' => 'Inspection notes and analysis'],
        'back' => ['label' => 'Back', 'icon' => 'heroicon-o-arrow-left', 'tooltip' => 'Back to the list'],
    ],
];
