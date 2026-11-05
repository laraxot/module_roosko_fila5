<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestratore Roosko. Presuppone i seeder di Warehouse (magazzino, aree, posizioni) e di
 * Waste (codici EER, impianto, autorizzazione con codici autorizzati) gia' eseguiti.
 *
 * Conferimenti, lotti e uscite non si seminano: nascono dai flussi (actions), cosi' registro
 * e movimenti restano coerenti.
 */
class RooskoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AreaWasteRuleSeeder::class,
        ]);
    }
}
