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
    email_verified_at?: string;
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
    status: 'published' | 'draft' | 'pending';
    quote_type?: 'spoken' | 'written' | 'testimony' | 'alleged' | 'paraphrased' | 'other';
    quote_type_note?: string;
    user_id?: number;
    user?: User;
    sources?: Source[];
    tags?: Tag[];
    categories?: Category[];
    created_at: string;
    updated_at: string;
}

export interface SourceForm {
    url: string;
    title: string;
    source_type: string;
    is_primary: boolean;
    archived_url: string;
}

export interface QuoteFormData {
    text: string;
    speaker: string;
    quote_type: string;
    quote_type_note: string;
    context: string;
    location: string;
    occurred_at: string;
    is_verified: boolean;
    is_featured: boolean;
    status: 'published' | 'draft' | 'pending';
    tags: string[];
    categories: string[];
    sources: SourceForm[];
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
