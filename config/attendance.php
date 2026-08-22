<?php

return [
    'client_visit' => [
        // Radius akurasi GPS maksimum (meter) supaya kunjungan otomatis dianggap 'wajar'
        'max_accuracy_m' => env('CLIENT_VISIT_MAX_ACCURACY_M', 50),
    ],
];