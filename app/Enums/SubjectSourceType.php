<?php

namespace App\Enums;

/**
 * Kinds of evidence behind a subject's full details. Separate from SourceType, which
 * describes where a quote was said (tweet, rally, interview…).
 */
enum SubjectSourceType: string
{
    case Study = 'study';
    case GovernmentReport = 'government_report';
    case CourtRuling = 'court_ruling';
    case OfficialData = 'official_data';
    case NewsArticle = 'news_article';
    case FactCheck = 'fact_check';
    case Book = 'book';
    case Video = 'video';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            SubjectSourceType::Study => 'Study / Research',
            SubjectSourceType::GovernmentReport => 'Government report',
            SubjectSourceType::CourtRuling => 'Court ruling',
            SubjectSourceType::OfficialData => 'Official data',
            SubjectSourceType::NewsArticle => 'News article',
            SubjectSourceType::FactCheck => 'Fact-check',
            SubjectSourceType::Book => 'Book',
            SubjectSourceType::Video => 'Video',
            SubjectSourceType::Other => 'Other',
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
