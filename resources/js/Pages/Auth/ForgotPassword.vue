<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

function submit(): void {
    form.post(route('password.email'));
}
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <p class="mb-4 text-sm text-muted-foreground">
            Forgot your password? Enter your email address and we'll send you a link to choose a new one.
        </p>

        <p v-if="status" class="mb-4 text-sm font-medium text-primary">
            {{ status }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Email" for="email" :error="form.errors.email">
                <Input id="email" type="email" v-model="form.email" required autofocus autocomplete="username" />
            </FormField>

            <div class="flex items-center justify-between pt-1">
                <Link :href="route('login')" class="text-sm text-primary underline underline-offset-4 hover:text-primary/80">
                    Back to sign in
                </Link>

                <Button type="submit" :disabled="form.processing">
                    Email reset link
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
