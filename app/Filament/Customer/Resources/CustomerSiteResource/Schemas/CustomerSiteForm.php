<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Modules\Geo\Filament\Resources\AddressResource\Schemas\AddressForm;
use Modules\Roosko\Actions\GetCustomerOperatorIdsAction;
use Modules\Waste\Models\Operator;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Cantiere del cliente: nome, numero di iscrizione RENTRI dell'unita' locale, attivo, indirizzo
 * (Address di Geo tramite HasAddress, come il form di Waste).
 *
 * Il soggetto si sceglie solo in creazione e solo se il cliente ne ha piu' d'uno, fra i suoi;
 * con un solo soggetto il campo non c'e' e lo assegna il server (CreateCustomerSite).
 */
class CustomerSiteForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        $addressSchema = app(AddressForm::class)->getFormSchema();
        unset($addressSchema['name'], $addressSchema['is_primary']);

        return [
            'operator_id' => Select::make('operator_id')
                ->options(static fn (): array => self::operatorOptions())
                ->visible(static fn (string $operation): bool => $operation === 'create' && \count(self::operatorOptions()) > 1)
                ->required(),
            'name' => TextInput::make('name')->required()->maxLength(255),
            'rentri_site_number' => TextInput::make('rentri_site_number')->maxLength(255),
            'is_active' => Toggle::make('is_active')->default(true),
            'address' => Section::make('address')
                ->relationship('address')
                ->mutateRelationshipDataBeforeCreateUsing(static fn (array $data): array => [...$data, 'is_primary' => true])
                ->schema($addressSchema)
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * Soggetti del cliente corrente: id => ragione sociale.
     *
     * @return array<int, string>
     */
    private static function operatorOptions(): array
    {
        $operatorIds = app(GetCustomerOperatorIdsAction::class)->execute(Filament::getTenant());

        $options = [];
        foreach (Operator::query()->whereIn('id', $operatorIds)->orderBy('business_name')->get(['id', 'business_name']) as $operator) {
            $options[$operator->id] = $operator->business_name;
        }

        return $options;
    }
}
