<?php

declare(strict_types=1);

namespace Modules\Roosko\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Roosko\Database\Factories\AreaWasteRuleFactory;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Warehouse\Models\Area;

/**
 * Mappa un codice autorizzato (AuthorizedWaste dell'autorizzazione d'impianto) su un'area
 * fisica del magazzino, con l'operazione di messa in riserva/deposito preliminare (R13/D15)
 * e un eventuale limite istantaneo per l'area.
 *
 * E' la base di WasteAuthorizationPlacementGuard: un lotto si stocca solo in aree con una
 * regola per il suo codice EER.
 *
 * @property int $id
 * @property int $area_id
 * @property int $authorized_waste_id
 * @property WasteOperationEnum $operation
 * @property string|null $max_storage_kg
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Area $area
 * @property-read AuthorizedWaste $authorizedWaste
 *
 * @method static AreaWasteRuleFactory factory($count = null, $state = [])
 */
class AreaWasteRule extends BaseModel
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'area_id',
        'authorized_waste_id',
        'operation',
        'max_storage_kg',
        'notes',
    ];

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return BelongsTo<AuthorizedWaste, $this>
     */
    public function authorizedWaste(): BelongsTo
    {
        return $this->belongsTo(AuthorizedWaste::class);
    }

    public function maxStorageKg(): ?float
    {
        return $this->max_storage_kg === null ? null : (float) $this->max_storage_kg;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'area_id' => 'integer',
            'authorized_waste_id' => 'integer',
            'operation' => WasteOperationEnum::class,
            'max_storage_kg' => 'decimal:3',
        ]);
    }
}
