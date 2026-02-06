<?php

return [
    'plan_matrix' => [
        'free' => ['health_dashboard'],
        'pro' => ['health_dashboard', 'health_notifications'],
        'enterprise' => ['health_dashboard', 'health_notifications', 'incidents', 'incident_sla'],
    ],
    'plan_aliases' => [
        // 'starter' => 'free',
        // 'business' => 'pro',
    ],
    'feature_labels' => [
        'health_dashboard' => 'Health Dashboard',
        'health_notifications' => 'Health Notifications',
        'incidents' => 'Incidents',
        'incident_sla' => 'Incident SLA',
        'mail_settings' => 'Mail Settings',
    ],
    'feature_descriptions' => [
        'health_dashboard' => 'Entegrasyon sagligi dashboardu.',
        'health_notifications' => 'Health bildirimleri ve otomatik uyarilar.',
        'incidents' => 'Incident listeleme ve yonetimi.',
        'incident_sla' => 'Incident SLA rozetleri ve metrikleri.',
        'mail_settings' => 'Mail ayarlari ve test mail islemleri.',
    ],
    'default_plan' => 'free',
];
