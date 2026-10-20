<?php

namespace App\Enums;

enum EmployeeRole: string
{
    case Mesero = 'Mesero';
    case Cocinero = 'Cocinero';
    case Bartender = 'Bartender';
    case Anfitrion = 'Anfitrion';
    case Gerente = 'Gerente';
    case Otro = 'Otro';

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    public function label(): string
    {
        return match ($this) {
            self::Mesero => 'Mesero',
            self::Cocinero => 'Cocinero',
            self::Bartender => 'Bartender',
            self::Anfitrion => 'Anfitrión',
            self::Gerente => 'Gerente',
            self::Otro => 'Otro',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Mesero => 'bg-primary',
            self::Cocinero => 'bg-danger',
            self::Bartender => 'bg-dark',
            self::Anfitrion => 'bg-info text-dark',
            self::Gerente => 'bg-purple',
            self::Otro => 'bg-secondary',
        };
    }
}
