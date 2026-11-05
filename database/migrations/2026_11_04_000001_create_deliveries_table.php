<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

/*
 * Conferimenti. fir_id, plant_site_id (Waste), warehouse_id (Warehouse) e weighing_id
 * (Weighing) sono FK cross-modulo: colonne indicizzate senza vincolo.
 *
 * Un FIR = un conferimento aperto: `open_fir_id` e' una colonna generata (virtuale) che vale
 * `fir_id` finche' il conferimento non e' respinto ne' eliminato, NULL altrimenti; il suo indice
 * unique impedisce due conferimenti vivi sullo stesso FIR anche con richieste concorrenti,
 * lasciando possibile un nuovo arrivo dopo un respingimento o un'eliminazione (soft delete).
 * `arrived_at` e' indicizzata: ordinamento di default, conferimenti del giorno, kg del cliente.
 */
return new class extends XotBaseMigration
{
    public function up(): void
    {
        $this->tableCreate(static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 32)->unique();
            $table->unsignedBigInteger('fir_id')->index();
            $table->unsignedBigInteger('plant_site_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('weighing_id')->nullable()->index();
            $table->string('status', 32)->index();
            $table->timestamp('arrived_at');
            $table->decimal('accepted_kg', 14, 3)->nullable();
            $table->decimal('rejected_kg', 14, 3)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->boolean('analysis_required')->default(false);
            $table->timestamp('closed_at')->nullable();
        });

        $this->tableUpdate(function (Blueprint $table): void {
            if (! $this->hasColumn('analysis_required')) {
                $table->boolean('analysis_required')->default(false);
            }
            if (! $this->hasColumn('closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }

            $this->updateTimestamps($table, true);
        });

        // Separata dal blocco precedente: la colonna generata legge `deleted_at`, che deve gia' esistere.
        $this->tableUpdate(function (Blueprint $table): void {
            if (! $this->hasColumn('open_fir_id')) {
                $table->unsignedBigInteger('open_fir_id')
                    ->nullable()
                    ->virtualAs("case when status <> 'rejected' and deleted_at is null then fir_id end")
                    ->unique('deliveries_open_fir_id_unique');
            }
        });

        $this->ensureIndexes([
            'deliveries' => [
                'deliveries_arrived_at_index' => ['arrived_at'],
            ],
        ]);
    }
};
