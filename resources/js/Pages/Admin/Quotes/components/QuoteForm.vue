<script setup lang="ts">
import type { Tag, Category, Speaker, SourceForm, QuoteFormData, QuoteTypeOption } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';
import { DatePicker } from '@/Components/ui/date-picker';
import { ComboboxMultiSelect } from '@/Components/ui/combobox-multi-select';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Plus } from 'lucide-vue-next';
import SourceFormRow from './SourceFormRow.vue';
import SpeakerAutocomplete from './SpeakerAutocomplete.vue';

const props = defineProps<{
    tags: Tag[];
    categories: Category[];
    speakers: Speaker[];
    quoteTypes: QuoteTypeOption[];
    initialValues?: Partial<QuoteFormData>;
    submitLabel: string;
    submitMethod: 'post' | 'put';
    submitRoute: string;
}>();

const form = useForm<QuoteFormData>({
    text: props.initialValues?.text ?? '',
    speaker: props.initialValues?.speaker ?? '',
    quote_type: props.initialValues?.quote_type ?? '',
    quote_type_note: props.initialValues?.quote_type_note ?? '',
    context: props.initialValues?.context ?? '',
    location: props.initialValues?.location ?? '',
    occurred_at: props.initialValues?.occurred_at ?? '',
    is_verified: props.initialValues?.is_verified ?? false,
    is_featured: props.initialValues?.is_featured ?? false,
    status: props.initialValues?.status ?? 'draft',
    tags: props.initialValues?.tags ?? [],
    categories: props.initialValues?.categories ?? [],
    sources: props.initialValues?.sources ?? [],
});

function addSource() {
    form.sources.push({
        _key: crypto.randomUUID(),
        url: '',
        title: '',
        source_type: '',
        is_primary: false,
        archived_url: '',
    });
}

function removeSource(index: number) {
    form.sources.splice(index, 1);
}

function submit() {
    if (props.submitMethod === 'post') {
        form.post(props.submitRoute);
    } else {
        form.put(props.submitRoute);
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Quote Content -->
        <Card>
            <CardHeader>
                <CardTitle>Quote Content</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="text">Quote Text *</Label>
                    <Textarea
                        id="text"
                        v-model="form.text"
                        placeholder="Enter the quote text..."
                        class="min-h-[120px]"
                    />
                    <p v-if="form.errors.text" class="text-sm text-destructive">{{ form.errors.text }}</p>
                </div>

                <div class="space-y-2">
                    <Label>Said By *</Label>
                    <SpeakerAutocomplete
                        v-model="form.speaker"
                        :speakers="speakers"
                        :error="form.errors.speaker"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Quote Type *</Label>
                    <Select v-model="form.quote_type">
                        <SelectTrigger>
                            <SelectValue placeholder="How was this quote delivered?" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="type in quoteTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.quote_type" class="text-sm text-destructive">{{ form.errors.quote_type }}</p>
                </div>

                <div v-if="form.quote_type === 'other'" class="space-y-2">
                    <Label for="quote_type_note">Describe the type</Label>
                    <Input
                        id="quote_type_note"
                        v-model="form.quote_type_note"
                        placeholder="e.g. satirical misquote, composite paraphrase..."
                    />
                    <p v-if="form.errors.quote_type_note" class="text-sm text-destructive">{{ form.errors.quote_type_note }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="context">Context</Label>
                    <Textarea
                        id="context"
                        v-model="form.context"
                        placeholder="Additional context or explanation..."
                        class="min-h-[80px]"
                    />
                    <p v-if="form.errors.context" class="text-sm text-destructive">{{ form.errors.context }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="location">Location</Label>
                    <Input
                        id="location"
                        v-model="form.location"
                        placeholder="Where was this said? (e.g. White House Press Briefing, Twitter)"
                    />
                    <p v-if="form.errors.location" class="text-sm text-destructive">{{ form.errors.location }}</p>
                </div>

                <div class="space-y-2">
                    <Label>Date Occurred</Label>
                    <DatePicker v-model="form.occurred_at" placeholder="Pick a date" />
                    <p v-if="form.errors.occurred_at" class="text-sm text-destructive">{{ form.errors.occurred_at }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Status & Flags -->
        <Card>
            <CardHeader>
                <CardTitle>Status & Flags</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label>Status *</Label>
                    <Select v-model="form.status">
                        <SelectTrigger>
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="published">Published</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <Switch
                            id="is_verified"
                            v-model="form.is_verified"
                        />
                        <Label for="is_verified">Verified</Label>
                    </div>

                    <div class="flex items-center gap-2">
                        <Switch
                            id="is_featured"
                            v-model="form.is_featured"
                        />
                        <Label for="is_featured">Featured</Label>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Tags -->
        <Card>
            <CardHeader>
                <CardTitle>Tags</CardTitle>
            </CardHeader>
            <CardContent>
                <ComboboxMultiSelect
                    v-model="form.tags"
                    :options="tags"
                    placeholder="Select or create tags..."
                    search-placeholder="Search tags..."
                    new-item-placeholder="New tag name..."
                    :error="form.errors.tags"
                />
            </CardContent>
        </Card>

        <!-- Categories -->
        <Card>
            <CardHeader>
                <CardTitle>Categories</CardTitle>
            </CardHeader>
            <CardContent>
                <ComboboxMultiSelect
                    v-model="form.categories"
                    :options="categories"
                    placeholder="Select or create categories..."
                    search-placeholder="Search categories..."
                    new-item-placeholder="New category name..."
                    :error="form.errors.categories"
                />
            </CardContent>
        </Card>

        <!-- Sources -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle>Sources</CardTitle>
                    <Button type="button" variant="outline" size="sm" @click="addSource">
                        <Plus class="mr-1 h-4 w-4" />
                        Add Source
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <p v-if="!form.sources.length" class="text-sm text-muted-foreground">
                    No sources added yet. Click "Add Source" to add one.
                </p>

                <SourceFormRow
                    v-for="(source, index) in form.sources"
                    :key="source._key"
                    v-model:source="form.sources[index]"
                    :index="index"
                    :errors="(form.errors as Record<string, string>)"
                    @remove="removeSource(index)"
                />
            </CardContent>
        </Card>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3">
            <Link
                :href="route('admin.quotes.index')"
                class="text-sm text-gray-600 hover:text-gray-900"
            >
                Cancel
            </Link>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
