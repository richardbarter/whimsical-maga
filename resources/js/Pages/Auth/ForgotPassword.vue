<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue";
import FormField from "@/Components/ui/form-field/FormField.vue";
import Input from "@/Components/ui/input/Input.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm } from "@inertiajs/vue3";

defineProps<{
  status?: string;
}>();

const form = useForm({
  email: "",
});

const submit = () => {
  form.post(route("password.email"));
};
</script>

<template>
  <GuestLayout>
    <Head title="Forgot Password" />

    <div class="mb-4 text-sm text-gray-600">
      Forgot your password? No problem. Just let us know your email address and
      we will email you a password reset link that will allow you to choose a
      new one.
    </div>

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit">
      <FormField label="Email" for="email" :error="form.errors.email">
        <Input
          id="email"
          type="email"
          v-model="form.email"
          required
          autocomplete="username"
        />
      </FormField>

      <div class="mt-4 flex items-center justify-end">
        <PrimaryButton
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Email Password Reset Link
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>
