<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Nuova uscita',
    'success' => 'Uscita creata',
    'error' => 'Uscita non creata',
    'fields' => [
        'warehouse_id' => ['label' => 'Magazzino'],
        'recipient_operator_id' => ['label' => 'Destinatario'],
        'recipient_site_id' => ['label' => 'Sede di destinazione'],
        'document_type' => ['label' => 'Documento'],
        'operation' => ['label' => 'Operazione a destino', 'helper_text' => 'Codice R/D dell\'impianto di destinazione'],
        'document_number' => ['label' => 'Numero documento', 'helper_text' => 'DDT o dichiarazione di conformità. Per il FIR lasciare vuoto: alla spedizione nasce in bozza nel pannello Rifiuti, dove si completano numero e trasportatore (il FIR digitale lo numera RENTRI)'],
        'notes' => ['label' => 'Note'],
    ],
];
