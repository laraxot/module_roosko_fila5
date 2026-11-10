<?php

declare(strict_types=1);

return [
    'home' => 'Back to the sign-in page',
    '403' => ['title' => 'Access denied', 'message' => 'Your user is not allowed to open this page. Ask the plant administrator if you need it.'],
    '404' => ['title' => 'Page not found', 'message' => 'The address does not exist or the record is not visible to your user.'],
    '419' => ['title' => 'Session expired', 'message' => 'The page was open for too long. Reload it and try again.'],
    '429' => ['title' => 'Too many requests', 'message' => 'Wait a few seconds and try again.'],
    '500' => ['title' => 'Server error', 'message' => 'Something went wrong. The error has been logged; if it happens again tell the administrator the time.'],
    '503' => ['title' => 'Maintenance in progress', 'message' => 'Roosko is being updated. Try again in a few minutes.'],
];
