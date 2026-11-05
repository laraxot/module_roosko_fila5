<?php

declare(strict_types=1);

return [
    'invalid_transition' => ':code: passaggio di stato da «:from» a «:to» non consentito',
    'delivery' => [
        'fir_already_received' => 'Il FIR :fir è già in carico con il conferimento :delivery',
        'wrong_recipient_site' => 'Il FIR :fir è destinato a un altro impianto',
        'not_weighed' => 'Il conferimento :delivery non ha una pesata in ingresso aperta',
        'units_mismatch' => 'Le unità sommano :units kg ma i kg accettati sono :accepted',
        'no_units' => 'Indicare almeno un\'unità',
    ],
    'authorization' => [
        'waste_not_authorized' => 'L\'impianto :site non è autorizzato per :ewc con operazione :operation',
        'plant_site_not_resolved' => 'Il magazzino :warehouse non ha regole d\'area: impossibile risalire all\'impianto',
        'plant_site_ambiguous' => 'Il magazzino :warehouse è mappato su più impianti: controllare le regole d\'area',
        'register_not_found' => 'Nessun registro di carico/scarico attivo per :site',
    ],
    'lot' => [
        'not_a_waste_lot' => 'L\'unità :unit non contiene un lotto',
        'placement_denied' => ':unit non può andare in :location: :reason',
        'eow_batch_code_required' => 'Un prodotto End of Waste richiede il codice di lotto EoW',
        'no_units_to_treat' => 'Nessuna unità da trattare',
        'order_without_inputs' => 'L\'ordine :order non ha input: aggiungere prima le unità da trattare',
    ],
    'shipment' => [
        'closed' => 'L\'uscita :shipment non accetta più unità',
        'unit_not_in_stock' => 'L\'unità :unit non è in giacenza',
        'other_warehouse' => 'L\'unità :unit è in un altro magazzino',
        'already_loaded' => 'L\'unità :unit è già sull\'uscita :shipment',
        'document_mismatch' => 'L\'unità :unit (:classification) non può viaggiare con :document',
        'destination_mismatch' => 'L\'unità :unit ha destino :destination, l\'uscita richiede :expected',
        'ewc_mismatch' => 'L\'unità :unit è :ewc, l\'uscita con FIR contiene già :expected',
        'empty' => 'L\'uscita :shipment non ha unità',
        'operation_required' => 'Per l\'uscita :shipment con FIR serve l\'operazione a destino',
        'document_number_required' => 'Per l\'uscita :shipment serve il numero del documento (DDT o dichiarazione di conformità)',
    ],
];
