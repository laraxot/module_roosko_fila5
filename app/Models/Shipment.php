<?php

declare(strict_types=1);

namespace Modules\Roosko\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Roosko\Database\Factories\ShipmentFactory;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Warehouse\Models\Warehouse;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;
use Modules\Weighing\Models\Weighing;

/**
 * Uscita dall'impianto: vendita End of Waste (DDT/dichiarazione di conformita'),
 * recupero/smaltimento/bonifica presso terzi (FIR).
 *
 * `operation` (R/D a destino) serve a compilare il FIR in uscita e la scrittura di scarico:
 * e' obbligatoria per le uscite con documento `fir`.
 *
 * @property int $id
 * @property string $code
 * @property int $warehouse_id
 * @property int $recipient_operator_id
 * @property int|null $recipient_site_id
 * @property int|null $fir_id
 * @property ShipmentDocumentEnum $document_type
 * @property string|null $document_number
 * @property WasteOperationEnum|null $operation
 * @property int|null $weighing_id
 * @property ShipmentStatusEnum $status
 * @property CarbonImmutable|null $dispatched_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Warehouse $warehouse
 * @property-read Operator $recipient
 * @property-read OperatorSite|null $recipientSite
 * @property-read Fir|null $fir
 * @property-read Weighing|null $weighing
 * @property-read Collection<int, ShipmentItem> $items
 *
 * @method static ShipmentFactory factory($count = null, $state = [])
 */
class Shipment extends BaseModel
{
    use SoftDeletes;

    public const string CODE_PREFIX = 'USC';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'warehouse_id',
        'recipient_operator_id',
        'recipient_site_id',
        'fir_id',
        'document_type',
        'document_number',
        'operation',
        'weighing_id',
        'status',
        'dispatched_at',
        'notes',
    ];

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Operator, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'recipient_operator_id');
    }

    /**
     * @return BelongsTo<OperatorSite, $this>
     */
    public function recipientSite(): BelongsTo
    {
        return $this->belongsTo(OperatorSite::class, 'recipient_site_id');
    }

    /**
     * @return BelongsTo<Fir, $this>
     */
    public function fir(): BelongsTo
    {
        return $this->belongsTo(Fir::class);
    }

    /**
     * @return BelongsTo<Weighing, $this>
     */
    public function weighing(): BelongsTo
    {
        return $this->belongsTo(Weighing::class);
    }

    /**
     * @return HasMany<ShipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function totalKg(): float
    {
        $sum = $this->items()->sum('quantity_kg');

        return round(is_numeric($sum) ? (float) $sum : 0.0, 3);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'warehouse_id' => 'integer',
            'recipient_operator_id' => 'integer',
            'recipient_site_id' => 'integer',
            'fir_id' => 'integer',
            'weighing_id' => 'integer',
            'document_type' => ShipmentDocumentEnum::class,
            'operation' => WasteOperationEnum::class,
            'status' => ShipmentStatusEnum::class,
            'dispatched_at' => 'immutable_datetime',
        ]);
    }
}
