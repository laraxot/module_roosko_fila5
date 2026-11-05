<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

/*
 * Lotti (contenuto delle StockUnit). ewc_code_id, producer_operator_id (Waste) e
 * processing_order_id (Processing) sono cross-modulo; delivery_id e' interno ma su
 * connessione di modulo: solo indice.
 */
return new class extends XotBaseMigration
{
    public function up(): void
    {
        $this->tableCreate(static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ewc_code_id')->index();
            $table->string('physical_state', 32);
            $table->json('hazard_properties')->nullable();
            $table->string('classification', 32)->index();
            $table->string('destination', 32)->index();
            $table->unsignedBigInteger('producer_operator_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_id')->nullable()->index();
            $table->unsignedBigInteger('processing_order_id')->nullable()->index();
            $table->string('eow_batch_code', 64)->nullable()->index();
            $table->string('eow_declaration_number', 64)->nullable();
            $table->text('notes')->nullable();
        });

        $this->tableUpdate(function (Blueprint $table): void {
            if (! $this->hasColumn('eow_declaration_number')) {
                $table->string('eow_declaration_number', 64)->nullable();
            }

            $this->updateTimestamps($table, true);
        });
    }
};
