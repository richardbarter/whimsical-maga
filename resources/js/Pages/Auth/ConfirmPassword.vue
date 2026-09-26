<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

function submit(): void {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <p class="mb-4 text-sm text-muted-foreground">
            This is a secure area of the application. Please confirm your password before continuing.
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Password" for="password" :error="form.errors.password">
                <Input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
            </FormField>

            <div class="flex justify-end pt-1">
                <Button type="submit" :disabled="form.processing">Confirm</Button>
            </div>
        </form>
    </GuestLayout>
</template>
