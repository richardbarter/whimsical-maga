<?php

namespace App\Enums;

enum SubjectStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            SubjectStatus::Draft => 'Draft',
            SubjectStatus::Published => 'Published',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->all();
    }
}
