# Database ERD - Whimsical Trump Quips

This file contains the Mermaid syntax for the database Entity Relationship Diagram.
You can paste this into https://mermaid.live/ to view or export as PNG/SVG.

```mermaid
erDiagram
    roles ||--o{ users : "has many"
    users ||--o{ quotes : creates
    quotes ||--o{ sources : "has many"
    quotes ||--o{ quote_tag : "has many"
    quotes ||--o{ quote_category : "has many"
    tags ||--o{ quote_tag : "has many"
    categories ||--o{ quote_category : "has many"

    roles {
        bigint id PK
        string name UK "admin, moderator, user"
        string description "nullable, describes role permissions"
        timestamp created_at
        timestamp updated_at
    }

    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        bigint role_id FK "references roles table"
        timestamp created_at
        timestamp updated_at
    }

    quotes {
        bigint id PK
        text text "the actual quote"
        text context "expanded explanation/context"
        date occurred_at "when quote was said/action happened"
        boolean is_verified "fact-checked flag"
        boolean is_featured "highlight special quotes"
        integer view_count "default 0"
        enum status "published, draft, pending"
        bigint user_id FK "nullable in Phase 1"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft deletes"
    }

    sources {
        bigint id PK
        bigint quote_id FK
        string url "link to source"
        string title "description of source"
        string source_type "tweet, article, video, speech, etc"
        boolean is_primary "main source flag"
        string archived_url "nullable, Internet Archive link"
        timestamp created_at
        timestamp updated_at
    }

    backgrounds {
        bigint id PK
        string file_path "path or URL to image"
        string alt_text "accessibility text"
        string title "optional title"
        string description "optional description"
        string credit "photographer/source credit"
        integer file_size "in bytes"
        string dimensions "e.g. 1920x1080"
        timestamp created_at
        timestamp updated_at
    }

    tags {
        bigint id PK
        string name UK
        string slug UK
        text description "nullable, for Phase 2+"
        timestamp created_at
        timestamp updated_at
    }

    categories {
        bigint id PK
        string name UK
        string slug UK
        text description "nullable, for Phase 2+"
        string color "hex color for UI, nullable"
        timestamp created_at
        timestamp updated_at
    }

    quote_tag {
        bigint id PK
        bigint quote_id FK
        bigint tag_id FK
        timestamp created_at
    }

    quote_category {
        bigint id PK
        bigint quote_id FK
        bigint category_id FK
        timestamp created_at
    }
```

## Tables Summary

### Core Tables
- **roles**: User role definitions (admin, moderator, user)
- **users**: All users (admin and regular users in Phase 3+)
- **quotes**: The main content - Trump quotes/actions
- **sources**: Multiple sources per quote (URLs, citations)
- **backgrounds**: Whimsical background images (separate pool)
- **tags**: Many-to-many tagging system
- **categories**: Many-to-many categorization system

### Pivot Tables
- **quote_tag**: Links quotes to tags
- **quote_category**: Links quotes to categories

## Key Relationships
- One role has many users
- One user creates many quotes
- One quote has many sources
- Quotes and backgrounds are separate pools (no direct relationship - randomly paired)
- Quotes have many tags (via quote_tag pivot)
- Quotes have many categories (via quote_category pivot)

## Notes
- Using BIGINT auto-incrementing IDs (Laravel default)
- Soft deletes on quotes table (deleted_at)
- Status enum for quotes: published, draft, pending
- user_id is nullable in Phase 1 (admin-created quotes don't need user attribution initially)
