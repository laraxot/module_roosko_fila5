<?php

declare(strict_types=1);

return [
    'invalid_transition' => ':code: status change from “:from” to “:to” is not allowed',
    'delivery' => [
        'fir_already_received' => 'FIR :fir is already in delivery :delivery',
        'fir_not_receivable' => 'FIR :fir is «:status» and cannot be received',
        'wrong_recipient_site' => 'FIR :fir is addressed to another plant',
        'not_weighed' => 'Delivery :delivery has no open inbound weighing',
        'units_mismatch' => 'Units add up to :units kg but accepted kg are :accepted',
        'no_units' => 'At least one unit is required',
    ],
    'authorization' => [
        'waste_not_authorized' => 'Plant :site is not authorized for :ewc with operation :operation',
        'plant_site_not_resolved' => 'Warehouse :warehouse has no area rules: the plant cannot be determined',
        'plant_site_ambiguous' => 'Warehouse :warehouse is mapped to several plants: check the area rules',
        'register_not_found' => 'No active load/unload register for :site',
    ],
    'lot' => [
        'not_a_waste_lot' => 'Unit :unit does not contain a lot',
        'placement_denied' => ':unit cannot go to :location: :reason',
        'unit_reserved' => 'Unit :unit is reserved for a shipment: remove it from the shipment before treating or moving it',
        'eow_batch_code_required' => 'An End of Waste product requires an EoW batch code',
        'no_units_to_treat' => 'No units to treat',
        'order_without_inputs' => 'Order :order has no inputs: add the units to treat first',
    ],
    'shipment' => [
        'closed' => 'Shipment :shipment no longer accepts units',
        'unit_not_in_stock' => 'Unit :unit is not in stock',
        'other_warehouse' => 'Unit :unit is in another warehouse',
        'already_loaded' => 'Unit :unit is already on shipment :shipment',
        'document_mismatch' => 'Unit :unit (:classification) cannot travel with :document',
        'destination_mismatch' => 'Unit :unit has destination :destination, the shipment requires :expected',
        'ewc_mismatch' => 'Unit :unit is :ewc, the FIR shipment already contains :expected',
        'empty' => 'Shipment :shipment has no units',
        'operation_required' => 'Shipment :shipment with a FIR requires the destination operation',
        'document_number_required' => 'Shipment :shipment requires the document number (delivery note or declaration of conformity)',
    ],
];
