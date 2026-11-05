<?php

declare(strict_types=1);

return [
    'title' => 'Operatore',
    'navigation' => [
        'label' => 'Operatore (scansione)',
        'group' => 'Piazzale',
        'icon' => 'heroicon-o-qr-code',
        'sort' => 1,
    ],
    'scan' => [
        'field' => 'Codice scansionato',
        'placeholder' => 'Scansiona un\'unità o una posizione',
        'placeholder_location' => 'Scansiona la posizione di destinazione',
        'hint' => 'Usa il grilletto del palmare oppure la fotocamera. Il campo resta sempre pronto.',
        'camera' => 'Fotocamera',
        'camera_close' => 'Chiudi',
        'not_found' => 'Codice «:code» non riconosciuto',
        'location_expected' => 'Serve la scansione di una posizione',
        'awaiting_location' => 'Unità :code: scansiona la posizione',
    ],
    'unit' => [
        'ewc' => 'EER',
        'weight' => 'Peso netto',
        'location' => 'Posizione',
        'status' => 'Stato',
        'classification' => 'Classificazione',
        'destination' => 'Destino',
    ],
    'location' => [
        'units' => 'Unità presenti',
        'empty' => 'Nessuna unità in questa posizione',
    ],
    'actions' => [
        'store' => 'Stocca',
        'move' => 'Sposta',
        'cancel' => 'Annulla',
        'clear' => 'Nuova scansione',
        'startTreatment' => ['label' => 'Avvia trattamento', 'icon' => 'heroicon-o-cog-6-tooth', 'tooltip' => 'Avvia un trattamento su questa unità'],
        'addToShipment' => ['label' => 'Aggiungi a uscita', 'icon' => 'heroicon-o-truck', 'tooltip' => 'Carica l\'unità su un\'uscita'],
        'reprintLabel' => ['label' => 'Ristampa etichetta', 'icon' => 'heroicon-o-printer', 'tooltip' => 'Ristampa l\'etichetta QR'],
        'adjustWeight' => ['label' => 'Rettifica peso', 'icon' => 'heroicon-o-scale', 'tooltip' => 'Correggi il peso netto con motivazione'],
    ],
    'fields' => [
        'machine_id' => ['label' => 'Macchina'],
        'operation' => ['label' => 'Operazione'],
        'shipment_id' => ['label' => 'Uscita'],
        'copies' => ['label' => 'Copie'],
        'new_net_kg' => ['label' => 'Nuovo peso netto'],
        'reason' => ['label' => 'Motivo'],
    ],
    'notifications' => [
        'error' => 'Operazione non eseguita',
        'stored' => 'Unità :code in :location',
        'treatment_started' => 'Trattamento avviato con :code',
        'added_to_shipment' => 'Unità :code caricata su :shipment',
        'label_queued' => 'Etichetta di :code in stampa',
        'weight_adjusted' => 'Peso di :code rettificato',
    ],
];
