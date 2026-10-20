<?php

declare(strict_types=1);

namespace App\Enum;

enum ImportIssueLevel: string
{
    case Error = 'error';
    case Warning = 'warning';

    public function label(): string
    {
        return match ($this) {
            self::Error => 'Ошибка',
            self::Warning => 'Предупреждение',
        };
    }
}
