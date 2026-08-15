<?php

namespace App\Modules\Location\Enums;

enum LocationLevel: string
{
    case DEPARTMENT = 'department';
    case PROVINCE = 'province';
    case DISTRICT = 'district';
}
