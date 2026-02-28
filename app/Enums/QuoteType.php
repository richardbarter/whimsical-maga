<?php

namespace App\Enums;

enum QuoteType: string
{
    case Spoken = 'spoken';
    case Written = 'written';
    case Testimony = 'testimony';
    case Alleged = 'alleged';
    case Paraphrased = 'paraphrased';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            QuoteType::Spoken => 'Spoken',
            QuoteType::Written => 'Written',
            QuoteType::Testimony => 'Testimony (under oath)',
            QuoteType::Alleged => 'Alleged',
            QuoteType::Paraphrased => 'Paraphrased',
            QuoteType::Other => 'Other',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->all();
    }
}
