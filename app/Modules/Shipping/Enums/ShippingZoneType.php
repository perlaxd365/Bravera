<?php

namespace App\Modules\Shipping\Enums;

enum ShippingZoneType: string
{
    case DEPARTMENT = 'department';
    case PROVINCE = 'province';
    case DISTRICT = 'district';
}
