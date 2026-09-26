<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Sign Up" />

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Name" for="name" :error="form.errors.name">
                <Input id="name" v-model="form.name" required autofocus autocomplete="name" />
            </FormField>

            <FormField label="Email" for="email" :error="form.errors.email">
                <Input id="email" type="email" v-model="form.email" required autocomplete="username" />
            </FormField>

            <FormField label="Password" for="password" :error="form.errors.password">
                <Input id="password" type="password" v-model="form.password" required autocomplete="new-password" />
            </FormField>

            <FormField label="Confirm Password" for="password_confirmation" :error="form.errors.password_confirmation">
                <Input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <div class="flex items-center justify-between pt-1">
                <Link :href="route('login')" class="text-sm text-primary underline underline-offset-4 hover:text-primary/80">
                    Already registered?
                </Link>

                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Creating account…' : 'Sign up' }}
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
