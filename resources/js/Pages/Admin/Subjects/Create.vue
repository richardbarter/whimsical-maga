<script setup lang="ts">
import type { SourceTypeOption, SubjectStatusOption } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import SubjectForm from './components/SubjectForm.vue';

defineProps<{
    subjectStatuses: SubjectStatusOption[];
    sourceTypes: SourceTypeOption[];
    imageUrlPrefix: string;
}>();
</script>

<template>
    <Head title="Add Subject" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">
                    Add Subject
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
                    :image-upload-url="null"
                    :image-url-prefix="imageUrlPrefix"
                    submit-label="Create Subject"
                    submit-method="post"
                    :submit-route="route('admin.subjects.store')"
                />
            </div>
        </div>
    </AdminLayout>
</template>
