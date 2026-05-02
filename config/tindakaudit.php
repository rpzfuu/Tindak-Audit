<?php

return [
    'app_code' => env('TINDAKAUDIT_APP_CODE', 'tindakaudit'),
    'real_access_niks' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('TINDAKAUDIT_REAL_ACCESS_NIKS', '')),
    ))),
];
