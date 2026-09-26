<script setup lang="ts">
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card } from '@/Components/ui/card';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const page = usePage();
const layout = computed(() => (page.props.auth.user.is_admin ? AdminLayout : AuthenticatedLayout));
</script>

<template>
    <Head title="Profile" />

    <component :is="layout">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">Profile</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <Card class="p-4 sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </Card>

                <Card class="p-4 sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </Card>

                <Card class="p-4 sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </Card>
            </div>
        </div>
    </component>
</template>
