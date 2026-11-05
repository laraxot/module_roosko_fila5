<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Accettazione del conferimento :code',
    'modal_description' => 'Suddividi il carico accettato nelle unità (contenitori): la somma dei pesi netti deve coincidere con i kg accettati. A ogni unità viene assegnato un QR e stampata l\'etichetta.',
    'success' => 'Conferimento :code accettato',
    'error' => 'Accettazione non registrata',
    'fields' => [
        'accepted_kg' => ['label' => 'Kg accettati'],
        'rejected_kg' => ['label' => 'Kg respinti'],
        'operation' => ['label' => 'Operazione', 'helper_text' => 'Es. R13 messa in riserva'],
        'rejection_reason' => ['label' => 'Motivo del respingimento parziale'],
        'units' => ['label' => 'Unità'],
        'container_type' => ['label' => 'Contenitore'],
        'net_weight_kg' => ['label' => 'Peso netto'],
        'tare_weight_kg' => ['label' => 'Tara contenitore'],
        'location_id' => ['label' => 'Posizione', 'helper_text' => 'Facoltativa: si può stoccare dopo, con la scansione'],
    ],
];
