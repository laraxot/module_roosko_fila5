<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Gate;
use Modules\Roosko\Models\Shipment;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Waste\Filament\Resources\FirResource;
use Modules\Waste\Models\Fir;
use Modules\Xot\Actions\Cast\SafeStringCastAction;
use Modules\Xot\Contracts\UserContract;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

class ShipmentInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'shipment' => Section::make('shipment')->schema([
                'grid' => Grid::make(4)->schema([
                    'code' => TextEntry::make('code')->copyable(),
                    'status' => TextEntry::make('status')->badge(),
                    'document_type' => TextEntry::make('document_type')->badge(),
                    'document_number' => TextEntry::make('document_number'),
                    'recipient' => TextEntry::make('recipient.business_name'),
                    'recipient_site' => TextEntry::make('recipientSite.name'),
                    'operation' => TextEntry::make('operation')->badge(),
                    'fir_number' => TextEntry::make('fir_number')
                        ->state(static fn (Shipment $record): ?string => self::firLabel($record))
                        ->url(static fn (Shipment $record): ?string => self::firUrl($record))
                        ->helperText(static fn (Shipment $record): ?string => self::fir($record)?->status === FirStatusEnum::DRAFT
                            ? SafeStringCastAction::cast(__('roosko::shipment_infolist.fields.fir_number.draft_help'))
                            : null),
                    'total_kg' => TextEntry::make('total_kg')->state(static fn (Shipment $record): float => $record->totalKg())->numeric(3)->suffix(' kg'),
                    'dispatched_at' => TextEntry::make('dispatched_at')->dateTime(),
                ]),
                'notes' => TextEntry::make('notes')->columnSpanFull(),
            ]),
        ];
    }

    private static function fir(Shipment $shipment): ?Fir
    {
        return $shipment->fir_id === null ? null : Fir::query()->find($shipment->fir_id);
    }

    /**
     * Il FIR d'uscita nasce in bozza, senza numero: si mostra comunque, cosi' si sa che esiste e
     * dove completarlo (trasportatore, mezzo) prima di emetterlo.
     */
    private static function firLabel(Shipment $shipment): ?string
    {
        $fir = self::fir($shipment);
        if (! $fir instanceof Fir) {
            return null;
        }
        $number = \is_string($fir->number) && $fir->number !== '' ? $fir->number : '#'.$fir->id;

        return $number.' · '.$fir->status->getLabel();
    }

    /**
     * Collegamento alla scheda del FIR nel pannello Rifiuti, solo per chi puo' aprirla.
     */
    private static function firUrl(Shipment $shipment): ?string
    {
        $fir = self::fir($shipment);
        $user = auth()->user();
        if (! $fir instanceof Fir || ! $user instanceof UserContract || ! $user->hasRole('waste::admin') || ! Gate::forUser($user)->allows('view', $fir)) {
            return null;
        }

        return FirResource::getUrl('view', ['record' => $fir], panel: 'waste::admin');
    }
}
