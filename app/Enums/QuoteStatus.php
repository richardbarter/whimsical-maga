<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            QuoteStatus::Draft => 'Draft',
            QuoteStatus::Pending => 'Pending',
            QuoteStatus::Published => 'Published',
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
