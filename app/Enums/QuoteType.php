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
}
