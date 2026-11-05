<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Roosko\Models\Profile;
use Modules\Xot\Database\Migrations\XotBaseMigration;

/*
 * Profili utente del progetto Roosko (main_module). user_id punta alla tabella users del
 * modulo User: FK cross-modulo, colonna indicizzata senza vincolo.
 */
return new class extends XotBaseMigration
{
    protected ?string $model_class = Profile::class;

    public function up(): void
    {
        $this->tableCreate(static function (Blueprint $table): void {
            $table->id();
            $table->string('uuid', 36)->nullable()->index();
            $table->string('user_id', 36)->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->decimal('credits', 12, 2)->default(0);
            $table->string('slug')->nullable();
            $table->json('extra')->nullable();
        });

        $this->tableUpdate(function (Blueprint $table): void {
            $this->updateTimestamps(table: $table, hasSoftDeletes: true);
        });
    }
};
