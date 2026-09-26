<?php

namespace App\Enums;

enum SourceType: string
{
    case Tweet = 'tweet';
    case Article = 'article';
    case Video = 'video';
    case Speech = 'speech';
    case Interview = 'interview';
    case PressConference = 'press_conference';
    case Rally = 'rally';
    case SocialMedia = 'social_media';
    case Book = 'book';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            SourceType::Tweet => 'Tweet',
            SourceType::Article => 'Article',
            SourceType::Video => 'Video',
            SourceType::Speech => 'Speech',
            SourceType::Interview => 'Interview',
            SourceType::PressConference => 'Press Conference',
            SourceType::Rally => 'Rally',
            SourceType::SocialMedia => 'Social Media',
            SourceType::Book => 'Book',
            SourceType::Other => 'Other',
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
