<?php

declare(strict_types=1);

namespace Modules\Roosko\Models;

use Modules\User\Models\BaseProfile;

/**
 * Profilo dell'utente nel progetto Roosko.
 *
 * Xot lo risolve come `Modules\{main_module}\Models\Profile` quando `xra.main_module` è Roosko.
 */
class Profile extends BaseProfile
{
    /** @var string */
    protected $connection = 'roosko';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'email',
        'first_name',
        'last_name',
        'slug',
        'extra',
    ];
}
