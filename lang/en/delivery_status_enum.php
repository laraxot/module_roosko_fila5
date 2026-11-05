<?php

declare(strict_types=1);

return [
    'label' => 'Delivery status',
    'values' => [
        'arrived' => ['label' => 'Arrived', 'color' => 'gray', 'icon' => 'heroicon-o-truck', 'description' => 'Load arrived at the plant, awaiting weighing or inspection'],
        'weighed' => ['label' => 'Weighed', 'color' => 'info', 'icon' => 'heroicon-o-scale', 'description' => 'Gross/tare weighing closed'],
        'inspecting' => ['label' => 'Inspecting', 'color' => 'warning', 'icon' => 'heroicon-o-magnifying-glass', 'description' => 'Conformity check or analysis in progress'],
        'accepted' => ['label' => 'Accepted', 'color' => 'success', 'icon' => 'heroicon-o-check-badge', 'description' => 'Load fully accepted'],
        'partially_accepted' => ['label' => 'Partially accepted', 'color' => 'warning', 'icon' => 'heroicon-o-adjustments-horizontal', 'description' => 'Part of the load rejected'],
        'rejected' => ['label' => 'Rejected', 'color' => 'danger', 'icon' => 'heroicon-o-x-circle', 'description' => 'Load rejected to the producer'],
        'closed' => ['label' => 'Closed', 'color' => 'gray', 'icon' => 'heroicon-o-lock-closed', 'description' => 'Case closed'],
    ],
];
