<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

/*
 * Righe delle uscite. stock_unit_id e' cross-modulo (Warehouse).
 */
return new class extends XotBaseMigration
{
    public function up(): void
    {
        $this->tableCreate(static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shipment_id')->index();
            $table->unsignedBigInteger('stock_unit_id')->index();
            $table->decimal('quantity_kg', 14, 3);
            $table->unique(['shipment_id', 'stock_unit_id']);
        });

        $this->tableUpdate(function (Blueprint $table): void {
            $this->updateTimestamps($table, false);
        });
    }
};
