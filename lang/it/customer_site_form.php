<?php

declare(strict_types=1);

return [
    'fields' => [
        'operator_id' => ['label' => 'Impresa', 'helper_text' => 'Impresa del tuo gruppo a cui intestare il cantiere'],
        'name' => ['label' => 'Nome del cantiere'],
        'rentri_site_number' => [
            'label' => 'Numero di iscrizione RENTRI dell\'unità locale',
            'helper_text' => 'Se il cantiere è iscritto a RENTRI come unità locale; altrimenti lascia vuoto. Dopo il primo FIR del cantiere la variazione si chiede all\'impianto',
        ],
        'is_active' => ['label' => 'Attivo', 'helper_text' => 'Spegni a cantiere chiuso: resta nello storico'],
    ],
    'sections' => [
        'address' => ['label' => 'Indirizzo del cantiere', 'heading' => 'Indirizzo del cantiere'],
    ],
];
