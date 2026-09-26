<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import { FormField } from '@/Components/ui/form-field';
import { Input } from '@/Components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<InstanceType<typeof Input> | null>(null);

const form = useForm({
    password: '',
});

function confirmUserDeletion(): void {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.$el.focus());
}

function deleteUser(): void {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeDialog(),
        onError: () => passwordInput.value?.$el.focus(),
        onFinish: () => {
            form.reset();
        },
    });
}

function closeDialog(): void {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
}

function onOpenChange(open: boolean): void {
    if (!open) {
        closeDialog();
    }
}
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-foreground">Delete Account</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before
                deleting your account, please download any data or information that you wish to retain.
            </p>
        </header>

        <Button variant="destructive" @click="confirmUserDeletion">Delete Account</Button>

        <Dialog :open="confirmingUserDeletion" @update:open="onOpenChange">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure you want to delete your account?</DialogTitle>
                    <DialogDescription>
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                        Please enter your password to confirm.
                    </DialogDescription>
                </DialogHeader>

                <form id="delete-account-form" @submit.prevent="deleteUser">
                    <FormField label="Password" for="delete-account-password" :error="form.errors.password">
                        <Input
                            id="delete-account-password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="Password"
                            autocomplete="current-password"
                        />
                    </FormField>
                </form>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeDialog">Cancel</Button>
                    <Button type="submit" form="delete-account-form" variant="destructive" :disabled="form.processing">
                        Delete Account
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>
