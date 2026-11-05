<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

/*
 * Uscite. warehouse_id, recipient_operator_id, recipient_site_id, fir_id, weighing_id sono
 * cross-modulo. `operation` (R/D a destino) non e' nella SPEC: serve al FIR in uscita.
 */
return new class extends XotBaseMigration
{
    public function up(): void
    {
        $this->tableCreate(static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 32)->unique();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('recipient_operator_id')->index();
            $table->unsignedBigInteger('recipient_site_id')->nullable()->index();
            $table->unsignedBigInteger('fir_id')->nullable()->index();
            $table->string('document_type', 32);
            $table->string('document_number', 64)->nullable();
            $table->string('operation', 8)->nullable();
            $table->unsignedBigInteger('weighing_id')->nullable()->index();
            $table->string('status', 32)->index();
            $table->timestamp('dispatched_at')->nullable();
            $table->text('notes')->nullable();
        });

        $this->tableUpdate(function (Blueprint $table): void {
            if (! $this->hasColumn('operation')) {
                $table->string('operation', 8)->nullable();
            }

            $this->updateTimestamps($table, true);
        });
    }
};
