<?php

namespace App\Enums;

enum InstitutionType: string
{
    case Ministry = 'ministry';
    case Agency = 'agency';
    case Department = 'department';
    case Parliament = 'parliament';
    case Judiciary = 'judiciary';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
