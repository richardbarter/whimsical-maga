<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Head } from '@inertiajs/vue3';

defineProps<{
    quotesCount?: number;
    backgroundsCount?: number;
    tagsCount?: number;
    categoriesCount?: number;
}>();
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Admin Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Total Quotes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-semibold">
                                {{ quotesCount ?? 0 }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Backgrounds
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-semibold">
                                {{ backgroundsCount ?? 0 }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Tags
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-semibold">
                                {{ tagsCount ?? 0 }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Categories
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-semibold">
                                {{ categoriesCount ?? 0 }}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
