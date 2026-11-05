<?php

declare(strict_types=1);

return [
    'label' => 'Documento',
    'values' => [
        'fir' => ['label' => 'FIR', 'color' => 'warning', 'icon' => 'heroicon-o-document-text', 'description' => 'Formulario di identificazione del rifiuto'],
        'transport_document' => ['label' => 'DDT', 'color' => 'info', 'icon' => 'heroicon-o-document', 'description' => 'Documento di trasporto (End of Waste, sottoprodotti)'],
        'eow_declaration' => ['label' => 'Dichiarazione di conformità EoW', 'color' => 'success', 'icon' => 'heroicon-o-document-check', 'description' => 'Dichiarazione di conformità del lotto End of Waste'],
    ],
];
