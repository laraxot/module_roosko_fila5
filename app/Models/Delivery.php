<?php

declare(strict_types=1);

namespace Modules\Roosko\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Roosko\Database\Factories\DeliveryFactory;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Models\Warehouse;
use Modules\Weighing\Models\Weighing;

/**
 * Conferimento: arrivo di un carico con FIR all'impianto, dalla pesata all'accettazione.
 * Stato e pesi si modificano solo con le actions (ReceiveDeliveryAction, WeighDeliveryAction,
 * AcceptDeliveryAction, RejectDeliveryAction).
 *
 * @property int $id
 * @property string $code
 * @property int $fir_id
 * @property int $plant_site_id
 * @property int $warehouse_id
 * @property int|null $weighing_id
 * @property DeliveryStatusEnum $status
 * @property CarbonImmutable $arrived_at
 * @property string|null $accepted_kg
 * @property string|null $rejected_kg
 * @property string|null $rejection_reason
 * @property string|null $inspection_notes
 * @property bool $analysis_required
 * @property CarbonImmutable|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Fir $fir
 * @property-read OperatorSite $plantSite
 * @property-read Warehouse $warehouse
 * @property-read Weighing|null $weighing
 * @property-read Collection<int, WasteLot> $wasteLots
 *
 * @method static DeliveryFactory factory($count = null, $state = [])
 */
class Delivery extends BaseModel
{
    use SoftDeletes;

    public const string CODE_PREFIX = 'CNF';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'fir_id',
        'plant_site_id',
        'warehouse_id',
        'weighing_id',
        'status',
        'arrived_at',
        'accepted_kg',
        'rejected_kg',
        'rejection_reason',
        'inspection_notes',
        'analysis_required',
        'closed_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'analysis_required' => false,
    ];

    /**
     * @return BelongsTo<Fir, $this>
     */
    public function fir(): BelongsTo
    {
        return $this->belongsTo(Fir::class);
    }

    /**
     * @return BelongsTo<OperatorSite, $this>
     */
    public function plantSite(): BelongsTo
    {
        return $this->belongsTo(OperatorSite::class, 'plant_site_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Weighing, $this>
     */
    public function weighing(): BelongsTo
    {
        return $this->belongsTo(Weighing::class);
    }

    /**
     * @return HasMany<WasteLot, $this>
     */
    public function wasteLots(): HasMany
    {
        return $this->hasMany(WasteLot::class);
    }

    public function acceptedKg(): ?float
    {
        return $this->accepted_kg === null ? null : (float) $this->accepted_kg;
    }

    public function rejectedKg(): ?float
    {
        return $this->rejected_kg === null ? null : (float) $this->rejected_kg;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'fir_id' => 'integer',
            'plant_site_id' => 'integer',
            'warehouse_id' => 'integer',
            'weighing_id' => 'integer',
            'status' => DeliveryStatusEnum::class,
            'arrived_at' => 'immutable_datetime',
            'accepted_kg' => 'decimal:3',
            'rejected_kg' => 'decimal:3',
            'analysis_required' => 'boolean',
            'closed_at' => 'immutable_datetime',
        ]);
    }
}
