<?php

$disabled = env('ANNABEL_CMS_DISABLED_MODULES', '');

return [
    'disabled' => is_string($disabled) && trim($disabled) !== ''
        ? array_values(array_filter(array_map('trim', explode(',', $disabled))))
        : [],
];
