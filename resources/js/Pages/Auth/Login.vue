<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Input from '@/Components/ui/input/Input.vue';
import Button from '@/Components/ui/button/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/ui/form-field/FormField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Admin Login" />

        <p v-if="status" class="mb-4 text-sm font-medium text-primary">
            {{ status }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Email" for="email" :error="form.errors.email">
                <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
            </FormField>

            <FormField label="Password" for="password" :error="form.errors.password">
                <Input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
            </FormField>

            <div class="flex items-center gap-2">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="text-sm text-muted-foreground">Remember me</span>
            </div>

            <div class="flex items-center justify-between pt-1">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-primary underline underline-offset-4 hover:text-primary/80"
                >
                    Forgot your password?
                </Link>

                <Button type="submit" :disabled="form.processing" class="ms-auto">
                    {{ form.processing ? 'Signing in…' : 'Sign in' }}
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
