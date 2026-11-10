<?php

declare(strict_types=1);

use Modules\Roosko\Tests\TestCase;

use function Pest\Laravel\get;

uses(TestCase::class);

test('la home di ogni lingua porta ai login dei pannelli, non al tema pubblico', function (): void {
    get('/it')
        ->assertOk()
        ->assertSee(route('filament.roosko::admin.auth.login'), false)
        ->assertSee(route('filament.roosko::customer.auth.login'), false)
        ->assertDontSee('Prediction market');
});

test('login e registrazione del tema portano al pannello: nessuna registrazione libera', function (): void {
    get('/it/auth/login')->assertRedirect(route('filament.roosko::admin.auth.login'));
    get('/it/auth/register')->assertRedirect(route('roosko.public.home', ['lang' => 'it']));
});

test('le pagine d\'errore sono quelle di Roosko', function (): void {
    get('/pagina-che-non-esiste-'.bin2hex(random_bytes(3)))
        ->assertNotFound()
        ->assertSee(__('roosko::errors.404.title'))
        ->assertDontSee('Predict');
});
