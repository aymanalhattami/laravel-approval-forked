<?php

namespace Approval\Enums;

enum ModificationStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Disapproved = 'disapproved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Disapproved => 'Disapproved',
        };
    }
    
    public static function toArray(): array
    {
        $enums = [];

        foreach (static::cases() as $status) {
            $enums[$status->value] = $status->label();
        }

        return $enums;
    }
}
