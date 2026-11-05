<?php

declare(strict_types=1);

return [
    'denied' => [
        'area_not_authorized' => 'l\'area della posizione :location non è autorizzata allo stoccaggio di rifiuti',
        'hazardous_not_segregated' => ':ewc è pericoloso e l\'area di :location non ammette rifiuti pericolosi',
        'ewc_not_allowed' => 'il codice :ewc non è autorizzato nell\'area di :location',
        'area_limit_exceeded' => 'limite dell\'area superato per :ewc (:current kg presenti + :unit kg > :limit kg)',
        'authorization_limit_exceeded' => 'limite autorizzato dell\'impianto superato per :ewc (:current kg presenti + :unit kg > :limit kg)',
    ],
];
