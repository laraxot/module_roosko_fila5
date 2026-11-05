<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Roosko\Actions\GetCustomerOperatorIdsAction;
use Modules\Xot\Filament\Resources\XotBaseResource;

/**
 * Base delle resource dell'area clienti (`roosko::customer`).
 *
 * Scope: il legame record → cliente e' indiretto (record → soggetto Waste → cliente), quindi lo
 * scope automatico di Filament e' spento (`$isScopedToTenant = false`: nessuno scope globale,
 * nessuna associazione automatica in creazione) e ogni query parte da `getEloquentQuery()`, che
 * filtra con i soggetti del cliente corrente (`GetCustomerOperatorIdsAction`). Elenco, record
 * aperto da URL (route binding), badge di navigazione e widget passano tutti da qui.
 *
 * Autorizzazione: le policy dei modelli (`DeliveryPolicy`, `FirPolicy`, `OperatorSitePolicy`)
 * sono del personale dell'impianto e valgono in tutti i pannelli; nell'area clienti l'accesso lo
 * danno il ruolo `roosko::customer` e l'appartenenza al cliente (controllati da Filament), le
 * operazioni consentite sono quelle di `getCustomerAbilities()` e solo sui record del cliente.
 */
abstract class CustomerBaseResource extends XotBaseResource
{
    protected static bool $isScopedToTenant = false;

    /**
     * Applica lo scope del cliente alla query del modello della resource.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<int>  $operatorIds  soggetti del cliente
     * @return Builder<TModel>
     */
    abstract public static function scopeToCustomer(Builder $query, array $operatorIds): Builder;

    /**
     * Operazioni consentite al cliente (nomi delle abilita' di policy: viewAny, view, create, update, ...).
     *
     * @return list<string>
     */
    abstract protected static function getCustomerAbilities(): array;

    /**
     * @return Builder<Model>
     */
    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        $operatorIds = app(GetCustomerOperatorIdsAction::class)->execute(Filament::getTenant());

        return static::scopeToCustomer(static::getModel()::query(), $operatorIds);
    }

    #[\Override]
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = match (true) {
            $action instanceof \BackedEnum => (string) $action->value,
            $action instanceof \UnitEnum => $action->name,
            default => $action,
        };

        if (! \in_array($ability, static::getCustomerAbilities(), true)) {
            return Response::deny();
        }

        if ($record instanceof Model && ! static::getEloquentQuery()->whereKey($record->getKey())->exists()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    /**
     * Conta solo i record del cliente (la base Xot conta l'intera tabella).
     */
    #[\Override]
    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getEloquentQuery()->count(), 0, ',', '.');
    }
}
