<?php

namespace App\Enums;

enum DrawSourceEnum: string
{
    case Api = 'api';
    case Csv = 'csv';
    case Manual = 'manual';
}
