<?php

declare(strict_types=1);

return [
    'denied' => [
        'area_not_authorized' => 'the area of location :location is not authorized for waste storage',
        'hazardous_not_segregated' => ':ewc is hazardous and the area of :location does not accept hazardous waste',
        'ewc_not_allowed' => 'code :ewc is not authorized in the area of :location',
        'area_limit_exceeded' => 'area limit exceeded for :ewc (:current kg present + :unit kg > :limit kg)',
        'authorization_limit_exceeded' => 'plant authorized limit exceeded for :ewc (:current kg present + :unit kg > :limit kg)',
    ],
];
