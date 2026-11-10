<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Arrivo di un carico',
    'modal_description' => 'Seleziona il FIR e, se il mezzo è sulla pesa a ponte, registra il lordo.',
    'success' => 'Conferimento registrato',
    'error' => 'Conferimento non registrato',
    'fields' => [
        'fir_id' => ['label' => 'FIR', 'placeholder' => 'Cerca per numero'],
        'plant_site_id' => ['label' => 'Impianto'],
        'warehouse_id' => ['label' => 'Magazzino'],
        'gross_kg' => ['label' => 'Peso lordo', 'helper_text' => 'Prima pesata in ingresso; vuoto se si pesa a pianale'],
        'scale_id' => ['label' => 'Bilancia', 'helper_text' => "Bilancia collegata che ha letto il peso; vuota se il peso e' scritto a mano"],
    ],
];
