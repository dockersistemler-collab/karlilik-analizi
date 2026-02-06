<?php

namespace App\Enums;

enum NotificationType: string
{
    case Critical = 'critical';
    case Operational = 'operational';
    case Info = 'info';
}