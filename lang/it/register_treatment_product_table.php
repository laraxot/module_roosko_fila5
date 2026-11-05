<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Registra prodotto del trattamento :code',
    'modal_description' => 'Ogni prodotto diventa un\'unità con QR ed etichetta. Se è ancora un rifiuto viene scritto il carico di nuova produzione sul registro; End of Waste e sottoprodotti no.',
    'success' => 'Unità :code registrata sul trattamento :order',
    'error' => 'Prodotto non registrato',
    'fields' => [
        'ewc_code_id' => ['label' => 'Codice EER'],
        'physical_state' => ['label' => 'Stato fisico'],
        'hazard_properties' => ['label' => 'Classi di pericolo (HP)', 'helper_text' => 'Obbligatorie per un codice EER pericoloso'],
        'classification' => ['label' => 'Classificazione'],
        'destination' => ['label' => 'Destino'],
        'eow_batch_code' => ['label' => 'Codice lotto End of Waste', 'helper_text' => 'Codice del lotto di produzione EoW'],
        'kind' => ['label' => 'Tipo output'],
        'container_type' => ['label' => 'Contenitore'],
        'net_weight_kg' => ['label' => 'Peso netto'],
        'tare_weight_kg' => ['label' => 'Tara contenitore'],
        'location_id' => ['label' => 'Posizione', 'helper_text' => 'Facoltativa: si può stoccare dopo, con la scansione'],
    ],
];
