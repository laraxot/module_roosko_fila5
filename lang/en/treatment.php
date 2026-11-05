<?php

declare(strict_types=1);

return [
    'title' => 'Treatments in progress',
    'navigation' => [
        'label' => 'Treatments in progress',
        'group' => 'Plant',
        'icon' => 'heroicon-o-cog-6-tooth',
        'sort' => 25,
    ],
    'fields' => [
        'code' => ['label' => 'Order'],
        'machine_name' => ['label' => 'Machine'],
        'operation_code' => ['label' => 'Operation'],
        'input_total_kg' => ['label' => 'Input (kg)'],
        'output_total_kg' => ['label' => 'Recorded output (kg)'],
        'started_at' => ['label' => 'Started'],
    ],
    'actions' => [
        'register_product' => ['label' => 'Register product', 'icon' => 'heroicon-o-plus-circle', 'tooltip' => 'Register a unit produced by the treatment, with lot and EWC code'],
        'complete' => ['label' => 'Complete treatment', 'icon' => 'heroicon-o-check-circle', 'tooltip' => 'Close the treatment with mass balance'],
    ],
];
