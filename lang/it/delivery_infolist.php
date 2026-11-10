<?php

declare(strict_types=1);

return [
    'sections' => [
        'delivery' => ['label' => 'Conferimento', 'heading' => 'Conferimento'],
        'weighing' => ['label' => 'Pesata', 'heading' => 'Pesata'],
        'acceptance' => ['label' => 'Accettazione', 'heading' => 'Accettazione'],
    ],
    'fields' => [
        'code' => ['label' => 'Codice'],
        'status' => ['label' => 'Stato'],
        'arrived_at' => ['label' => 'Arrivo'],
        'analysis_required' => ['label' => 'Analisi richiesta'],
        'fir_number' => ['label' => 'Numero FIR'],
        'ewc_code' => ['label' => 'Codice EER'],
        'plantSite' => ['name' => ['label' => 'Impianto']],
        'warehouse' => ['name' => ['label' => 'Magazzino']],
        'gross_kg' => ['label' => 'Lordo'],
        'tare_kg' => ['label' => 'Tara'],
        'net_kg' => ['label' => 'Netto'],
        'fir_quantity' => ['label' => 'Quantità dichiarata sul FIR', 'estimated' => 'stimata'],
        'accepted_kg' => ['label' => 'Accettati'],
        'rejected_kg' => ['label' => 'Respinti'],
        'rejection_reason' => ['label' => 'Motivo del respingimento'],
        'inspection_notes' => ['label' => 'Note di ispezione'],
    ],
];
