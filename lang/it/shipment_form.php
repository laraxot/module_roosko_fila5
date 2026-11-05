<?php

declare(strict_types=1);

return [
    'fields' => [
        'code' => ['label' => 'Codice uscita'],
        'document_number' => ['label' => 'Numero documento', 'helper_text' => 'Numero del DDT o della dichiarazione di conformità (obbligatorio per End of Waste)'],
        'operation' => ['label' => 'Operazione a destino', 'helper_text' => 'Codice R/D dell\'impianto di destinazione (obbligatorio con FIR)'],
        'notes' => ['label' => 'Note'],
    ],
];
