<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Complete treatment :code?',
    'modal_description' => 'The mass balance between inputs and recorded products is calculated and the unit lineage is written. After closing, no more products can be registered.',
    'success' => 'Treatment :code completed',
    'error' => 'Treatment not completed',
    'fields' => [
        'force' => ['label' => 'Force closing', 'helper_text' => 'Close even if the mass loss exceeds the configured threshold'],
    ],
];
