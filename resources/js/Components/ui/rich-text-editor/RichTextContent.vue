<script setup lang="ts">
import { RICH_TEXT_CLASSES } from './utils'

/**
 * Displays full details written with RichTextEditor. Import this file directly rather than
 * through ./index.ts, so public pages don't pull in the editor (TipTap).
 *
 * This is the app's only v-html. It's safe because the server sanitizes the HTML to an
 * allow-list (RichTextSanitizer). The note lives here, not as a template comment, because a
 * comment would make the template multi-root and drop classes passed in by the parent.
 */
defineProps<{
    /** HTML already cleaned by RichTextSanitizer on the server, when saved and again when sent. */
    html: string
}>()
</script>

<template>
    <div class="rich-text-content" :class="RICH_TEXT_CLASSES" v-html="html" />
</template>

<style scoped>
/* Wide tables scroll sideways on phones instead of squashing their columns */
@media (max-width: 639px) {
    .rich-text-content :deep(table) {
        display: block;
        overflow-x: auto;
    }
}
</style>
