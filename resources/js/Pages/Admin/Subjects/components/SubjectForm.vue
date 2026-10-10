<script setup lang="ts">
import type { SourceTypeOption, SubjectFormData, SubjectStatusOption } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { FormField } from '@/Components/ui/form-field';
import { RichTextEditor } from '@/Components/ui/rich-text-editor';
import { SourceFormRow } from '@/Components/ui/source-form-row';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Plus } from 'lucide-vue-next';

const props = defineProps<{
    subjectStatuses: SubjectStatusOption[];
    /** Evidence types (study, court ruling…), not the quote source types. */
    sourceTypes: SourceTypeOption[];
    /** Where images are uploaded; null on the create page, before the subject has an id. */
    imageUploadUrl: string | null;
    /** URL that uploaded images start with. */
    imageUrlPrefix: string;
    initialValues?: Partial<SubjectFormData>;
    submitLabel: string;
    submitMethod: 'post' | 'put';
    submitRoute: string;
}>();

const form = useForm<SubjectFormData>({
    name: props.initialValues?.name ?? '',
    status: props.initialValues?.status ?? 'draft',
    full_details: props.initialValues?.full_details ?? '',
    sources: props.initialValues?.sources ?? [],
});

function addSource() {
    form.sources.push({
        _key: crypto.randomUUID(),
        url: '',
        title: '',
        source_type: '',
        archived_url: '',
    });
}

function removeSource(index: number) {
    form.sources.splice(index, 1);
}

function submit() {
    form.submit(props.submitMethod, props.submitRoute);
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <FormField label="Title *" for="name" :error="form.errors.name">
                    <p class="text-sm text-muted-foreground">
                        The argument or claim the full details explain, shared by every quote that makes it.
                    </p>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Mail-in ballots are rife with fraud"
                    />
                </FormField>

                <FormField label="Status *" :error="form.errors.status">
                    <p class="text-sm text-muted-foreground">
                        Drafts stay hidden on quote pages until they're published.
                    </p>
                    <Select v-model="form.status">
                        <SelectTrigger>
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in subjectStatuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Full Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
                <p class="text-sm text-muted-foreground">
                    The full explanation, shown on the page of every quote linked to this subject. Keep it
                    general; details about one quote belong in that quote's context or reality check.
                    Pasting from Google Docs or Word keeps bold, headings, lists, links and tables.
                </p>
                <RichTextEditor
                    v-model="form.full_details"
                    label="Full details"
                    :image-upload-url="imageUploadUrl"
                    :image-url-prefix="imageUrlPrefix"
                    :invalid="!!form.errors.full_details"
                />
                <p v-if="form.errors.full_details" class="text-sm text-destructive">
                    {{ form.errors.full_details }}
                </p>
            </CardContent>
        </Card>

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
                <p class="text-sm text-muted-foreground">
                    Add the studies, reports and rulings the explanation relies on. They're listed, numbered,
                    under the full details on the quote page, in this order.
                </p>

                <SourceFormRow
                    v-for="(source, index) in form.sources"
                    :key="source._key"
                    v-model:source="form.sources[index]"
                    :index="index"
                    :source-types="sourceTypes"
                    :errors="form.errors as Record<string, string>"
                    :show-primary="false"
                    @remove="removeSource(index)"
                />
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Link
                :href="route('admin.subjects.index')"
                class="text-sm text-muted-foreground hover:text-foreground"
            >
                Cancel
            </Link>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
