<?php

declare(strict_types=1);

namespace Modules\Roosko\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Roosko\Database\Factories\ShipmentItemFactory;
use Modules\Warehouse\Models\StockUnit;

/**
 * Unita' caricata su un'uscita. `quantity_kg` e' il peso netto al momento del carico
 * (snapshot per il documento di trasporto; la fonte resta la StockUnit).
 *
 * @property int $id
 * @property int $shipment_id
 * @property int $stock_unit_id
 * @property string $quantity_kg
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shipment $shipment
 * @property-read StockUnit $stockUnit
 *
 * @method static ShipmentItemFactory factory($count = null, $state = [])
 */
class ShipmentItem extends BaseModel
{
    /** @var list<string> */
    protected $fillable = [
        'shipment_id',
        'stock_unit_id',
        'quantity_kg',
    ];

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * @return BelongsTo<StockUnit, $this>
     */
    public function stockUnit(): BelongsTo
    {
        return $this->belongsTo(StockUnit::class);
    }

    public function quantityKg(): float
    {
        return (float) $this->quantity_kg;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'shipment_id' => 'integer',
            'stock_unit_id' => 'integer',
            'quantity_kg' => 'decimal:3',
        ]);
    }
}
