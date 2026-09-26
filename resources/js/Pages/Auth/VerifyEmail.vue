<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

function submit(): void {
    form.post(route('verification.send'));
}
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <p class="mb-4 text-sm text-muted-foreground">
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the
            link we just emailed to you? If you didn't receive the email, we will gladly send you another.
        </p>

        <p v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-primary">
            A new verification link has been sent to the email address you provided during registration.
        </p>

        <form @submit.prevent="submit" class="flex items-center justify-between pt-1">
            <Button type="submit" :disabled="form.processing">Resend verification email</Button>

            <Button as-child variant="link" class="px-0">
                <Link :href="route('logout')" method="post" as="button">Log out</Link>
            </Button>
        </form>
    </GuestLayout>
</template>
