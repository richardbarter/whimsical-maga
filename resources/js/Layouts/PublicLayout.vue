<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen">
        <!-- Public layout is minimal - the background and quote take center stage -->
        <main>
            <slot />
        </main>
    </div>
</template>
