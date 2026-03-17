<?php

return [
    'basic_enabled' => filter_var((string) env('ADMIN_BASIC_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'basic_user' => env('ADMIN_BASIC_USER', ''),
    'basic_pass' => env('ADMIN_BASIC_PASS', ''),
];
