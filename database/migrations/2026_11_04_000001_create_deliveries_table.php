<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

/*
 * Conferimenti. fir_id, plant_site_id (Waste), warehouse_id (Warehouse) e weighing_id
 * (Weighing) sono FK cross-modulo: colonne indicizzate senza vincolo.
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
    }
};
