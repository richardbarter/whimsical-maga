export type QuoteStatus = 'published' | 'draft' | 'pending';

export type QuoteTypeValue = 'spoken' | 'written' | 'testimony' | 'alleged' | 'paraphrased' | 'other';

/** A {value, label} pair for a select, as produced by the PHP enums' options() methods. */
export interface SelectOption<T extends string = string> {
    value: T;
    label: string;
}

export type QuoteTypeOption = SelectOption<QuoteTypeValue>;

export type QuoteStatusOption = SelectOption<QuoteStatus>;

export type SourceTypeOption = SelectOption;

export type SubjectStatus = 'draft' | 'published';

export type SubjectStatusOption = SelectOption<SubjectStatus>;

export interface SpeakerAlias {
    id: number;
    speaker_id: number;
    alias: string;
}

export interface Speaker {
    id: number;
    name: string;
    slug: string;
    description?: string;
    aliases?: SpeakerAlias[];
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_admin: boolean;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
    description?: string;
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    color?: string;
}

export interface Source {
    id: number;
    quote_id: number;
    url: string;
    title?: string;
    source_type?: string;
    is_primary: boolean;
    archived_url?: string;
}

/** A study, report, ruling or article that a subject's full details rely on. */
export interface SubjectSource {
    id: number;
    subject_id: number;
    url: string;
    title: string | null;
    source_type: string | null;
    archived_url: string | null;
    position: number;
}

/** A reusable explanation shared by the quotes that make the same argument. */
export interface Subject {
    id: number;
    name: string;
    slug: string;
    full_details?: string | null;
    status: SubjectStatus;
    quotes_count?: number;
    sources?: SubjectSource[];
    created_at: string;
    updated_at: string;
}

export interface SubjectFormData {
    name: string;
    status: SubjectStatus;
    /** HTML from the rich text editor; '' when empty. */
    full_details: string;
    sources: SourceForm[];
}

export interface Quote {
    id: number;
    text: string;
    speaker_id?: number;
    speaker?: Speaker;
    slug: string;
    context?: string;
    location?: string;
    occurred_at?: string;
    published_at?: string;
    is_verified: boolean;
    is_featured: boolean;
    view_count: number;
    status: QuoteStatus;
    quote_type?: QuoteTypeValue;
    quote_type_note?: string;
    claim: string | null;
    reality_check: string | null;
    user_id?: number;
    user?: User;
    sources?: Source[];
    tags?: Tag[];
    categories?: Category[];
    subjects?: Subject[];
    /** Home feed only: a published subject with written full details is linked. */
    has_full_details?: boolean;
    created_at: string;
    updated_at: string;
}

/** A subject as the public quote page receives it (QuotePageService::subjects()). */
export interface QuotePageSubject {
    id: number;
    name: string;
    slug: string;
    status: SubjectStatus;
    /** Sanitized on the server; null when no full details have been written. */
    full_details_html: string | null;
    sources: Pick<SubjectSource, 'url' | 'title' | 'source_type' | 'archived_url'>[];
}

/** Props for the public quote page (Pages/Public/Quotes/Show.vue). */
export interface QuotePageProps {
    quote: Quote;
    /** Published subjects, plus drafts for admins, in the admin's order. */
    subjects: QuotePageSubject[];
    /** True for an unpublished quote, which only admins can reach. */
    isPreview: boolean;
    quoteTypes: QuoteTypeOption[];
    sourceTypes: SourceTypeOption[];
    /** Labels for subject sources (study, court ruling…), which differ from quote source types. */
    subjectSourceTypes: SourceTypeOption[];
}

/** Cursor for the home page's batched quote feed. */
export interface QuoteFeed {
    seed: number;
    page: number;
    hasMore: boolean;
}

export interface Background {
    id: number;
    file_path: string;
    url: string;
    alt_text?: string;
    title?: string;
    description?: string;
    credit?: string;
    source_url?: string;
    file_size?: number;
    dimensions?: string;
    created_at: string;
    updated_at: string;
}

export interface BackgroundFormData {
    image: File | null;
    title: string;
    alt_text: string;
    description: string;
    credit: string;
    source_url: string;
}

export interface ComboboxItem {
    id: number | null;
    name: string;
}

/** One row of SourceFormRow. Subject sources have no primary flag, so is_primary is optional. */
export interface SourceForm {
    _key: string;
    url: string;
    title: string;
    source_type: string;
    is_primary?: boolean;
    archived_url: string;
}

export interface QuoteFormData {
    text: string;
    speaker: string;
    quote_type: string;
    quote_type_note: string;
    claim: string;
    reality_check: string;
    context: string;
    location: string;
    occurred_at: string;
    is_verified: boolean;
    is_featured: boolean;
    status: QuoteStatus;
    tags: ComboboxItem[];
    categories: ComboboxItem[];
    subjects: ComboboxItem[];
    sources: SourceForm[];
}

export interface SavedContext {
    id: number;
    subject: string;
    body: string;
    tags?: Tag[];
    created_at: string;
    updated_at: string;
}

export interface SavedContextFormData {
    subject: string;
    body: string;
    tags: ComboboxItem[];
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        success?: string;
        error?: string;
    };
};
