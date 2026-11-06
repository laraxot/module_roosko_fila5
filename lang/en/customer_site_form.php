<?php

declare(strict_types=1);

return [
    'fields' => [
        'operator_id' => ['label' => 'Company', 'helper_text' => 'Company of your group the site belongs to'],
        'name' => ['label' => 'Site name'],
        'rentri_site_number' => [
            'label' => 'RENTRI local unit registration number',
            'helper_text' => 'If the site is registered with RENTRI as a local unit; otherwise leave empty. After the first FIR of the site, ask the plant to change it',
        ],
        'is_active' => ['label' => 'Active', 'helper_text' => 'Turn off when the site closes: it stays in the history'],
    ],
    'sections' => [
        'address' => ['label' => 'Site address', 'heading' => 'Site address'],
    ],
];
