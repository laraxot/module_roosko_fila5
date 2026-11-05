<?php

declare(strict_types=1);

return [
    'modal_heading' => 'Completare il trattamento :code?',
    'modal_description' => 'Viene calcolato il bilancio di massa tra input e prodotti registrati e scritta la genealogia delle unità. Dopo la chiusura non si possono più registrare prodotti.',
    'success' => 'Trattamento :code completato',
    'error' => 'Trattamento non completato',
    'fields' => [
        'force' => ['label' => 'Forza la chiusura', 'helper_text' => 'Chiudi anche se la perdita di massa supera la soglia configurata'],
    ],
];
