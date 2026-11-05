<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

/*
 * Regole di stoccaggio per area. area_id (Warehouse) e authorized_waste_id (Waste) sono
 * cross-modulo. Una regola per coppia area/codice autorizzato.
 */
return new class extends XotBaseMigration
{
    public function up(): void
    {
        $this->tableCreate(static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('area_id')->index();
            $table->unsignedBigInteger('authorized_waste_id')->index();
            $table->string('operation', 8);
            $table->decimal('max_storage_kg', 14, 3)->nullable();
            $table->text('notes')->nullable();
            $table->unique(['area_id', 'authorized_waste_id']);
        });

        $this->tableUpdate(function (Blueprint $table): void {
            $this->updateTimestamps($table, true);
        });
    }
};
