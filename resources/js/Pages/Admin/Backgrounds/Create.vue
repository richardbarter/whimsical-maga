<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import BackgroundForm from './components/BackgroundForm.vue';
</script>

<template>
    <Head title="Add Background" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">Add Background</h2>
                <Link :href="route('admin.backgrounds.index')" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Backgrounds
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <BackgroundForm
                    submit-label="Add Background"
                    submit-method="post"
                    :submit-route="route('admin.backgrounds.store')"
                />
            </div>
        </div>
    </AdminLayout>
</template>
