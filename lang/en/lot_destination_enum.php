<?php

declare(strict_types=1);

return [
    'label' => 'Destination',
    'values' => [
        'internal_treatment' => ['label' => 'Internal treatment', 'color' => 'info', 'icon' => 'heroicon-o-cog-6-tooth', 'description' => 'Stays in the plant for treatment'],
        'sale' => ['label' => 'Sale', 'color' => 'success', 'icon' => 'heroicon-o-banknotes', 'description' => 'Sold as End of Waste or by-product'],
        'external_recovery' => ['label' => 'Third-party recovery', 'color' => 'primary', 'icon' => 'heroicon-o-arrow-path', 'description' => 'Sent to recovery (R) at another plant'],
        'disposal' => ['label' => 'Disposal', 'color' => 'danger', 'icon' => 'heroicon-o-fire', 'description' => 'Sent to disposal (D)'],
        'remediation' => ['label' => 'Remediation', 'color' => 'warning', 'icon' => 'heroicon-o-beaker', 'description' => 'Intended for site remediation'],
    ],
];
