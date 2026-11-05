<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Reject delivery :code?',
    'modal_description' => 'The load does not enter the plant: no units, no register load entry. The rejection is recorded on the FIR (and sent to RENTRI for digital FIRs).',
    'success' => 'Delivery :code rejected',
    'error' => 'Rejection not recorded',
    'fields' => [
        'reason' => ['label' => 'Reason'],
    ],
];
