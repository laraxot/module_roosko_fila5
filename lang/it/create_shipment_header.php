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
        'document_number' => ['label' => 'Numero documento', 'helper_text' => 'DDT o dichiarazione di conformità; per il FIR si genera alla spedizione'],
        'notes' => ['label' => 'Note'],
    ],
];
