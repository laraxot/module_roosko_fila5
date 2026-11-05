<?php

declare(strict_types=1);

namespace Modules\Roosko\Exceptions;

use DomainException;

/**
 * Base delle eccezioni di dominio Roosko: il messaggio e' gia' tradotto
 * (`lang/{locale}/exceptions.php`) e si mostra cosi' com'e' all'operatore.
 */
abstract class RooskoException extends DomainException
{
    /**
     * Messaggio per `Assert::true($from->canTransitionTo($to), ...)` nelle actions.
     */
    public static function transitionMessage(string $code, \BackedEnum $from, \BackedEnum $to): string
    {
        return self::translated('invalid_transition', [
            'code' => $code,
            'from' => (string) $from->value,
            'to' => (string) $to->value,
        ]);
    }

    /**
     * @param  array<string, string|int|float>  $replace
     */
    protected static function translated(string $key, array $replace = []): string
    {
        $message = __('roosko::exceptions.'.$key, $replace);

        return \is_string($message) ? $message : 'roosko::exceptions.'.$key;
    }
}
