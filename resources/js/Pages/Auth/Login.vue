<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    canRegister?: boolean;
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
        <Head title="Sign In" />

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
                <Checkbox id="remember" v-model="form.remember" />
                <Label for="remember" class="font-normal text-muted-foreground">Remember me</Label>
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

            <p v-if="canRegister" class="mt-4 text-center text-sm text-muted-foreground">
                Don't have an account?
                <Link :href="route('register')" class="text-primary underline underline-offset-4 hover:text-primary/80">
                    Sign up
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
