<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Card, CardContent } from '@/Components/ui/card';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Manage Categories" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">Categories</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card>
                    <CardContent class="p-6 text-muted-foreground">
                        Category management coming soon. Categories can be created from the quote form in the meantime.
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
