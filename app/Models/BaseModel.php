<?php

declare(strict_types=1);

namespace Modules\Roosko\Models;

use Modules\Xot\Models\XotBaseModel;

/**
 * Base dei modelli Roosko: connessione `roosko`.
 *
 * Le relazioni verso i modelli degli altri moduli (Waste, Warehouse, Weighing, Processing)
 * sono normali BelongsTo/MorphOne: Eloquent interroga il modello collegato sulla SUA
 * connessione, quindi funzionano anche con database separati (niente join tra moduli).
 */
abstract class BaseModel extends XotBaseModel
{
<<<<<<< HEAD
    /** @var string */
=======
    /** @var string|null */
>>>>>>> laraxot/dev
    protected $connection = 'roosko';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'id' => 'integer',
        ]);
    }
}
