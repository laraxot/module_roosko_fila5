<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * Service worker della UI operatore. Servito da una route e non da `assets/roosko/`
 * perche' lo scope di un service worker non puo' superare la cartella da cui viene
 * servito senza l'header `Service-Worker-Allowed`: cosi' copre tutto il pannello `/roosko/`.
 */
Route::get('/roosko/sw.js', static fn () => response()->file(
    module_path('Roosko', 'public/sw.js'),
    [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Service-Worker-Allowed' => '/roosko/',
        'Cache-Control' => 'no-cache',
    ],
))->name('roosko.service-worker');

/*
 * Ingresso pubblico. Roosko non ha un sito pubblico: la radice e la home di ogni lingua mostrano la
 * scelta fra pannello dell'impianto e area clienti; login e registrazione del tema portano ai login dei
 * pannelli (i clienti li crea l'impianto, niente registrazione libera). Prima il tema pubblico mostrava
 * la home e il footer di un altro prodotto e un login con chiavi di traduzione grezze.
 * Si spegne con `roosko.public_entry.enabled` = false.
 */
if (config('roosko.public_entry.enabled', true) === true) {
    $home = static fn () => view('roosko::public.home', [
        'plantUrl' => route('filament.roosko::admin.auth.login'),
        'customerUrl' => route('filament.roosko::customer.auth.login'),
    ]);
    $locales = 'it|en';

    Route::middleware('web')->group(static function () use ($home, $locales): void {
        // `/` resta quello di Cms, che porta a `/{lingua}`.
        Route::get('/{lang}', $home)->where('lang', $locales)->name('roosko.public.home');
        Route::get('/{lang}/auth/login', static fn () => redirect()->route('filament.roosko::admin.auth.login'))->where('lang', $locales);
        Route::get('/{lang}/auth/register', static fn (string $lang) => redirect()->route('roosko.public.home', ['lang' => $lang]))->where('lang', $locales);
    });
}
