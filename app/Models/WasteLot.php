<?php

declare(strict_types=1);

namespace Modules\Roosko\Models;

use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Database\Factories\WasteLotFactory;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Operator;
use Modules\Warehouse\Models\StockUnit;

/**
 * Contenuto di dominio di una StockUnit (morph `subject` dell'unita'): cosa c'e' dentro il
 * contenitore, da dove viene e dove deve andare. Peso, posizione e stato restano sulla
 * StockUnit di Warehouse: qui non si duplicano.
 *
 * Origine: `delivery_id` per i lotti conferiti da terzi, `processing_order_id` per quelli
 * prodotti da un trattamento interno.
 *
 * @property int $id
 * @property int $ewc_code_id
 * @property PhysicalStateEnum $physical_state
 * @property Collection<int, HazardPropertyEnum> $hazard_properties
 * @property LotClassificationEnum $classification
 * @property LotDestinationEnum $destination
 * @property int|null $producer_operator_id
 * @property int|null $delivery_id
 * @property int|null $processing_order_id
 * @property string|null $eow_batch_code
 * @property string|null $eow_declaration_number
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EwcCode $ewcCode
 * @property-read Operator|null $producer
 * @property-read Delivery|null $delivery
 * @property-read ProcessingOrder|null $processingOrder
 * @property-read StockUnit|null $stockUnit
 *
 * @method static WasteLotFactory factory($count = null, $state = [])
 */
class WasteLot extends BaseModel
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'ewc_code_id',
        'physical_state',
        'hazard_properties',
        'classification',
        'destination',
        'producer_operator_id',
        'delivery_id',
        'processing_order_id',
        'eow_batch_code',
        'eow_declaration_number',
        'notes',
    ];

    /**
     * L'unita' di magazzino che contiene il lotto (StockUnit.subject).
     *
     * @return MorphOne<StockUnit, $this>
     */
    public function stockUnit(): MorphOne
    {
        return $this->morphOne(StockUnit::class, 'subject');
    }

    /**
     * @return BelongsTo<EwcCode, $this>
     */
    public function ewcCode(): BelongsTo
    {
        return $this->belongsTo(EwcCode::class);
    }

    /**
     * @return BelongsTo<Operator, $this>
     */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'producer_operator_id');
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<ProcessingOrder, $this>
     */
    public function processingOrder(): BelongsTo
    {
        return $this->belongsTo(ProcessingOrder::class);
    }

    /**
     * Pericoloso: codice EER con asterisco o almeno una caratteristica HP.
     */
    public function isHazardous(): bool
    {
        return $this->ewcCode->is_hazardous || $this->hazard_properties->isNotEmpty();
    }

    /**
     * @return list<HazardPropertyEnum>
     */
    public function hazardPropertyList(): array
    {
        return array_values($this->hazard_properties->all());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'ewc_code_id' => 'integer',
            'physical_state' => PhysicalStateEnum::class,
            'hazard_properties' => AsEnumCollection::of(HazardPropertyEnum::class),
            'classification' => LotClassificationEnum::class,
            'destination' => LotDestinationEnum::class,
            'producer_operator_id' => 'integer',
            'delivery_id' => 'integer',
            'processing_order_id' => 'integer',
        ]);
    }
}
