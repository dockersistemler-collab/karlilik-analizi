<?php

return [
    'plans_catalog' => [
        'free' => [
            'name' => 'Free',
            'price_monthly' => 0,
            'features' => ['health_dashboard'],
            'recommended' => false,
            'contact_sales' => false,
        ],
        'pro' => [
            'name' => 'Pro',
            'price_monthly' => 999,
            'features' => ['health_dashboard', 'health_notifications'],
            'recommended' => true,
            'contact_sales' => false,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly' => 0,
            'features' => ['health_dashboard', 'health_notifications', 'incidents', 'incident_sla'],
            'recommended' => false,
            'contact_sales' => true,
        ],
    ],
];
