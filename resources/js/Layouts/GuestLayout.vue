<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-12">
        <div class="mb-8 text-center">
            <Link href="/" class="text-3xl font-bold text-primary">
                Whimsical MAGA
            </Link>
            <p class="mt-1 text-sm text-muted-foreground">Admin Portal</p>
        </div>

        <div class="w-full max-w-md rounded-xl bg-card px-8 py-8 shadow-lg">
            <slot />
        </div>
    </div>
</template>
