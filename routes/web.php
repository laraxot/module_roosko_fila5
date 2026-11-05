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
