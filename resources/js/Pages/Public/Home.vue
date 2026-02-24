<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    quote?: {
        id: number;
        text: string;
        context?: string;
        occurred_at?: string;
    };
    background?: {
        id: number;
        file_path: string;
        alt_text?: string;
    };
}>();
</script>

<template>
    <Head title="Home" />

    <PublicLayout>
        <div
            class="relative flex min-h-screen items-center justify-center bg-cover bg-center"
            :style="background ? `background-image: url('/storage/${background.file_path}')` : ''"
        >
            <!-- Overlay for better text readability -->
            <div class="absolute inset-0 bg-black/30"></div>

            <!-- Quote Card -->
            <div class="relative z-10 mx-4 max-w-3xl rounded-2xl bg-white/90 p-8 shadow-2xl backdrop-blur-sm sm:p-12">
                <blockquote v-if="quote" class="text-center">
                    <p class="text-2xl font-medium leading-relaxed text-gray-800 sm:text-3xl">
                        "{{ quote.text }}"
                    </p>
                    <footer v-if="quote.context" class="mt-6 text-gray-600">
                        {{ quote.context }}
                    </footer>
                </blockquote>

                <div v-else class="text-center text-gray-600">
                    <p class="text-xl">Welcome to Whimsical MAGA Quips</p>
                    <p class="mt-2 text-sm">No quotes available yet. Check back soon!</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
