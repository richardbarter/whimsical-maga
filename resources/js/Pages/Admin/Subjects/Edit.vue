<script setup lang="ts">
import type { SourceTypeOption, Subject, SubjectFormData, SubjectStatusOption } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/utils';
import SubjectForm from './components/SubjectForm.vue';

const props = defineProps<{
    subject: Subject;
    subjectStatuses: SubjectStatusOption[];
    sourceTypes: SourceTypeOption[];
    imageUrlPrefix: string;
}>();

const initialValues: SubjectFormData = {
    name: props.subject.name,
    status: props.subject.status,
    full_details: props.subject.full_details ?? '',
    sources: (props.subject.sources ?? []).map(s => ({
        _key: crypto.randomUUID(),
        url: s.url,
        title: s.title ?? '',
        source_type: s.source_type ?? '',
        archived_url: s.archived_url ?? '',
    })),
};
</script>

<template>
    <Head title="Edit Subject" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">
                    Edit Subject
                </h2>
                <Link
                    :href="route('admin.subjects.index')"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Subjects
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <SubjectForm
                    :subject-statuses="subjectStatuses"
                    :source-types="sourceTypes"
                    :image-upload-url="route('admin.subjects.images.store', subject.id)"
                    :image-url-prefix="imageUrlPrefix"
                    :initial-values="initialValues"
                    submit-label="Update Subject"
                    submit-method="put"
                    :submit-route="route('admin.subjects.update', subject.id)"
                />

                <!-- Read-only metadata -->
                <div class="mt-6 rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                    <dl class="grid grid-cols-3 gap-4">
                        <div>
                            <dt class="font-medium">Slug</dt>
                            <dd class="mt-0.5 break-all font-mono text-xs">{{ subject.slug }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">Created</dt>
                            <dd class="mt-0.5">{{ formatDate(subject.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">Last Updated</dt>
                            <dd class="mt-0.5">{{ formatDate(subject.updated_at) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
