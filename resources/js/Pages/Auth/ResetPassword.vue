<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Email" for="email" :error="form.errors.email">
                <Input id="email" type="email" v-model="form.email" required autofocus autocomplete="username" />
            </FormField>

            <FormField label="New Password" for="password" :error="form.errors.password">
                <Input id="password" type="password" v-model="form.password" required autocomplete="new-password" />
            </FormField>

            <FormField label="Confirm New Password" for="password_confirmation" :error="form.errors.password_confirmation">
                <Input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <div class="flex justify-end pt-1">
                <Button type="submit" :disabled="form.processing">Reset password</Button>
            </div>
        </form>
    </GuestLayout>
</template>
