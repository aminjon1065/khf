<?php

$trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));

return [
    'trusted' => match (strtolower($trustedProxies)) {
        '', 'none' => null,
        '*' => '*',
        default => array_values(array_filter(array_map(
            trim(...),
            explode(',', $trustedProxies),
        ))),
    },
];
