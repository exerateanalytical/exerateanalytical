<?php

namespace App\Enums;

enum RiskTier: string
{
    case Low = 'low';
    case Moderate = 'moderate';
    case High = 'high';

    public function label(): string
    {
        return match($this) {
            RiskTier::Low => 'Low Risk',
            RiskTier::Moderate => 'Moderate Risk',
            RiskTier::High => 'High Risk',
        };
    }

    public function color(): string
    {
        return match($this) {
            RiskTier::Low => 'green',
            RiskTier::Moderate => 'yellow',
            RiskTier::High => 'red',
        };
    }
}
