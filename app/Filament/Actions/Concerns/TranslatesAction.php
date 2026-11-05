<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions\Concerns;

use Modules\Xot\Actions\GetTransKeyAction;

/**
 * Testi del modale di una Filament action dal suo file lang (`lang/{locale}/{nome_action}.php`,
 * es. `accept_delivery_header.php`): la stessa chiave che GetTransKeyAction usa per le
 * etichette automatiche dei campi del modale. Etichetta e icona del bottone arrivano invece
 * dal file della Resource (`actions.{nome}`), via Lang.
 */
trait TranslatesAction
{
    /**
     * @param  array<string, string|int|float>  $replace
     */
    protected static function transAction(string $key, array $replace = []): string
    {
        $prefix = app(GetTransKeyAction::class)->execute(static::class);
        $value = __($prefix.'.'.$key, $replace);

        return \is_string($value) ? $value : $prefix.'.'.$key;
    }
}
