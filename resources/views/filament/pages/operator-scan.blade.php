@php
    /** @var \Modules\Roosko\Filament\Pages\OperatorScanPage $this */
    $unit = $this->unit();
    $lot = $this->lot();
    $location = $this->scannedLocation();
    $t = static fn (string $key, array $replace = []): string => \Modules\Roosko\Filament\Pages\OperatorScanPage::trans($key, false, $replace);
@endphp

<x-filament-panels::page>
    <style>
        .roosko-op { display: grid; gap: 1rem; max-width: 40rem; margin-inline: auto; }
        .roosko-op-scan { display: flex; gap: .5rem; align-items: stretch; }
        .roosko-op-scan input { flex: 1; min-width: 0; font-size: 1.25rem; padding: .75rem 1rem; border-radius: .75rem; border: 2px solid rgb(var(--primary-500, 20 184 166)); background: transparent; }
        .roosko-op-scan input:focus { outline: 3px solid rgb(var(--primary-300, 94 234 212)); }
        .roosko-op-camera-btn { min-width: 3.5rem; border-radius: .75rem; font-size: 1.5rem; }
        .roosko-op-hint { font-size: .875rem; opacity: .75; }
        .roosko-op-awaiting { padding: .75rem 1rem; border-radius: .75rem; background: rgba(234, 179, 8, .15); font-weight: 600; display: flex; justify-content: space-between; gap: .5rem; align-items: center; }
        .roosko-op-card { border-radius: 1rem; padding: 1rem; border: 1px solid rgba(127, 127, 127, .25); display: grid; gap: .5rem; }
        .roosko-op-code { font-size: 1.5rem; font-weight: 700; letter-spacing: .02em; word-break: break-all; }
        .roosko-op-grid { display: grid; grid-template-columns: max-content 1fr; gap: .25rem .75rem; font-size: 1rem; }
        .roosko-op-grid dt { opacity: .7; }
        .roosko-op-grid dd { font-weight: 600; margin: 0; }
        .roosko-op-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        .roosko-op-actions > * { width: 100%; }
        .roosko-op-actions .fi-btn { width: 100%; min-height: 3rem; justify-content: center; }
        .roosko-op-list { display: grid; gap: .5rem; }
        .roosko-op-list button { text-align: start; padding: .75rem 1rem; border-radius: .75rem; border: 1px solid rgba(127, 127, 127, .25); display: flex; justify-content: space-between; gap: .5rem; }
        .roosko-op-video { width: 100%; border-radius: 1rem; background: #000; aspect-ratio: 1 / 1; object-fit: cover; }
        .roosko-op-error { color: rgb(220, 38, 38); }
    </style>

    <div class="roosko-op">
        {{-- Campo sempre a fuoco: i lettori keyboard wedge digitano il codice seguito da Invio.
             inputmode="none" evita che la tastiera virtuale copra lo schermo a ogni rifocus; il bottone
             tastiera passa a inputmode="text" (scelta ricordata sul dispositivo) per digitare il codice a mano. --}}
        <form class="roosko-op-scan" wire:submit="submitScan"
              x-data="{
                  keyboard: (() => { try { return localStorage.getItem('roosko-op-keyboard') === '1'; } catch (e) { return false; } })(),
                  toggleKeyboard() {
                      this.keyboard = ! this.keyboard;
                      try { localStorage.setItem('roosko-op-keyboard', this.keyboard ? '1' : '0'); } catch (e) {}
                      this.$refs.scan.blur();
                      this.$nextTick(() => this.$refs.scan.focus());
                  },
                  refocus() {
                      setTimeout(() => {
                          const active = document.activeElement;
                          const busy = document.querySelector('.fi-modal-open') || document.body.dataset.rooskoCamera === 'open';
                          if (! busy && (active === null || active === document.body)) { this.$refs.scan.focus(); }
                      }, 150);
                  },
              }"
              x-init="$refs.scan.focus()"
              @focusout="refocus()"
              x-on:livewire:navigated.window="$refs.scan.focus()">
            <input
                x-ref="scan"
                type="text"
                inputmode="none"
                x-bind:inputmode="keyboard ? 'text' : 'none'"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                enterkeyhint="go"
                wire:model="scan"
                aria-label="{{ $t('scan.field') }}"
                placeholder="{{ $awaiting === 'location' ? $t('scan.placeholder_location') : $t('scan.placeholder') }}"
            >
            <x-filament::button type="button" color="gray" class="roosko-op-camera-btn" x-on:click="toggleKeyboard()" icon="heroicon-o-command-line" :tooltip="$t('scan.keyboard')" x-bind:aria-pressed="keyboard ? 'true' : 'false'">
                <span class="sr-only">{{ $t('scan.keyboard') }}</span>
            </x-filament::button>
            <div x-data="rooskoScanner" data-roosko-scanner>
                <x-filament::button type="button" color="gray" class="roosko-op-camera-btn" x-on:click="start()" icon="heroicon-o-camera" :tooltip="$t('scan.camera')">
                    <span class="sr-only">{{ $t('scan.camera') }}</span>
                </x-filament::button>

                <x-filament::modal id="roosko-camera" width="md" :close-by-clicking-away="false">
                    <video id="roosko-camera-video" class="roosko-op-video" playsinline muted></video>
                    <p class="roosko-op-error" x-show="error" x-text="error"></p>
                    <x-slot name="footer">
                        <x-filament::button color="gray" x-on:click="stop()">{{ $t('scan.camera_close') }}</x-filament::button>
                    </x-slot>
                </x-filament::modal>
            </div>
        </form>
        <p class="roosko-op-hint">{{ $t('scan.hint') }}</p>

        @if ($awaiting === 'location' && $unit)
            <div class="roosko-op-awaiting" role="status">
                <span>{{ $t('scan.awaiting_location', ['code' => $unit->code]) }}</span>
                <x-filament::button size="sm" color="gray" wire:click="cancelPending">{{ $t('actions.cancel') }}</x-filament::button>
            </div>
        @endif

        @if ($unit)
            <section class="roosko-op-card" aria-live="polite">
                <div class="roosko-op-code">{{ $unit->code }}</div>
                <dl class="roosko-op-grid">
                    <dt>{{ $t('unit.ewc') }}</dt><dd>{{ $this->ewcLabel($lot) ?? '—' }}</dd>
                    <dt>{{ $t('unit.weight') }}</dt><dd>{{ number_format((float) $unit->net_weight_kg, 3, ',', '.') }} kg</dd>
                    <dt>{{ $t('unit.location') }}</dt><dd>{{ $unit->location?->code ?? '—' }}</dd>
                    <dt>{{ $t('unit.status') }}</dt><dd><x-filament::badge :color="$unit->status->getColor()">{{ $unit->status->getLabel() }}</x-filament::badge></dd>
                    @if ($lot)
                        <dt>{{ $t('unit.classification') }}</dt><dd>{{ $lot->classification->getLabel() }}</dd>
                        <dt>{{ $t('unit.destination') }}</dt><dd>{{ $lot->destination->getLabel() }}</dd>
                    @endif
                </dl>

                <div class="roosko-op-actions">
                    {{-- Stocca/Sposta: solo unita' in giacenza libere (non riservate su un'uscita) e con l'abilita' `store`. --}}
                    @if (in_array($unit->status, [\Modules\Warehouse\Enums\StockUnitStatusEnum::PENDING, \Modules\Warehouse\Enums\StockUnitStatusEnum::STORED], true) && $this->allows('store'))
                        <x-filament::button icon="heroicon-o-map-pin" wire:click="startStore" :disabled="$awaiting === 'location'">
                            {{ $unit->location_id ? $t('actions.move') : $t('actions.store') }}
                        </x-filament::button>
                    @endif
                    @if ($this->startTreatmentAction->isVisible()) {{ $this->startTreatmentAction }} @endif
                    @if ($this->addToShipmentAction->isVisible()) {{ $this->addToShipmentAction }} @endif
                    @if ($this->reprintLabelAction->isVisible()) {{ $this->reprintLabelAction }} @endif
                    @if ($this->adjustWeightAction->isVisible()) {{ $this->adjustWeightAction }} @endif
                    <x-filament::button color="gray" icon="heroicon-o-x-mark" wire:click="clear">{{ $t('actions.clear') }}</x-filament::button>
                </div>
            </section>
        @elseif ($location)
            <section class="roosko-op-card" aria-live="polite">
                <div class="roosko-op-code">{{ $location->code }}</div>
                <p class="roosko-op-hint">{{ $t('location.units') }}</p>
                <div class="roosko-op-list">
                    @forelse ($this->locationUnits() as $present)
                        <button type="button" wire:click="selectUnit({{ $present->id }})" wire:key="unit-{{ $present->id }}">
                            <span>{{ $present->code }}</span>
                            <span>{{ number_format((float) $present->net_weight_kg, 3, ',', '.') }} kg</span>
                        </button>
                    @empty
                        <p>{{ $t('location.empty') }}</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
