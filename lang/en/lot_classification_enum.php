<?php

declare(strict_types=1);

return [
    'label' => 'Classification',
    'values' => [
        'waste' => ['label' => 'Waste', 'color' => 'warning', 'icon' => 'heroicon-o-trash', 'description' => 'Waste: moved with a FIR and recorded in the load/unload register'],
        'end_of_waste' => ['label' => 'End of Waste', 'color' => 'success', 'icon' => 'heroicon-o-sparkles', 'description' => 'End-of-waste status reached: saleable product'],
        'by_product' => ['label' => 'By-product', 'color' => 'info', 'icon' => 'heroicon-o-cube', 'description' => 'By-product, not a waste'],
    ],
];
