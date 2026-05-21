<?php

return [
    'stores' => [
        'minimum_total_orders' => 10,   //max 100
        'minimum_avg_rating' => 4,  //max 5
        'minimum_success_rate' => 40,   //max 100
        'minimum_account_age_months' => 3,  //max 12
    ],
    'providers' => [
        'minimum_total_trips' => 10,  //max 100
        'minimum_avg_rating' => 2,  //max 5
        'minimum_success_rate' => 40,  //max 100
        'minimum_account_age_months' => 3, //max 12
    ],
];
