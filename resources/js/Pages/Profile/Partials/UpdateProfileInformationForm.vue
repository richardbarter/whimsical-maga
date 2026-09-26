<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-foreground">Profile Information</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-5">
            <FormField label="Name" for="name" :error="form.errors.name">
                <Input id="name" v-model="form.name" required autocomplete="name" />
            </FormField>

            <FormField label="Email" for="email" :error="form.errors.email">
                <Input id="email" type="email" v-model="form.email" required autocomplete="username" />
            </FormField>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="space-y-2">
                <p class="text-sm text-foreground">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-sm text-primary underline underline-offset-4 hover:text-primary/80"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <p v-show="status === 'verification-link-sent'" class="text-sm font-medium text-primary">
                    A new verification link has been sent to your email address.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">Save</Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
