<?php

declare(strict_types=1);

return [
    'title' => 'Lavorazioni in corso',
    'navigation' => [
        'label' => 'Lavorazioni in corso',
        'group' => 'Impianto',
        'icon' => 'heroicon-o-cog-6-tooth',
        'sort' => 25,
    ],
    'fields' => [
        'code' => ['label' => 'Ordine'],
        'machine_name' => ['label' => 'Macchina'],
        'operation_code' => ['label' => 'Operazione'],
        'input_total_kg' => ['label' => 'Input (kg)'],
        'output_total_kg' => ['label' => 'Output registrati (kg)'],
        'started_at' => ['label' => 'Avviato'],
    ],
    'actions' => [
        'register_product' => ['label' => 'Registra prodotto', 'icon' => 'heroicon-o-plus-circle', 'tooltip' => 'Registra un\'unità prodotta dal trattamento, con lotto e codice EER'],
        'complete' => ['label' => 'Completa trattamento', 'icon' => 'heroicon-o-check-circle', 'tooltip' => 'Chiudi il trattamento con bilancio di massa'],
    ],
];
