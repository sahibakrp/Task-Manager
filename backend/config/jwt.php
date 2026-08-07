<?php

return [
    'secret' => getenv('JWT_SECRET') ?: 'change_this_secret',
    'algo' => 'HS256',
    'expires_in' => 3600, // seconds
];
