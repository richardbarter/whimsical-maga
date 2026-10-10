<script setup lang="ts">
import { Badge } from "@/Components/ui/badge";
import { Card } from "@/Components/ui/card";
import RichTextContent from "@/Components/ui/rich-text-editor/RichTextContent.vue";
import type { QuotePageSubject, SelectOption } from "@/types";
import { BookOpen, BookOpenText } from "lucide-vue-next";
import SourceList from "./SourceList.vue";

defineProps<{
  subject: QuotePageSubject;
  /** Element id for the title, the target of the "On this page" links. */
  anchorId: string;
  /** Labels for the subject source types (study, court ruling…). */
  sourceTypes: SelectOption[];
}>();
</script>

<template>
  <section :aria-labelledby="anchorId">
    <Card class="p-6 sm:p-10">
      <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">
        <BookOpenText class="size-4 text-primary" aria-hidden="true" />
        Full details
      </p>

      <div class="mt-2 flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
        <h2 :id="anchorId" class="scroll-mt-6 text-2xl font-bold leading-tight text-foreground">
          {{ subject.name }}
        </h2>
        <!-- Only admins receive draft subjects -->
        <Badge
          v-if="subject.status === 'draft'"
          class="border-transparent bg-accent text-accent-foreground"
        >
          Draft, only admins see this
        </Badge>
      </div>

      <RichTextContent
        v-if="subject.full_details_html"
        class="mt-6"
        :html="subject.full_details_html"
      />
      <p v-else class="mt-4 italic text-muted-foreground">
        The full details for this subject haven't been written yet.
      </p>

      <div v-if="subject.sources.length" class="mt-8 border-t border-border pt-6">
        <h3 class="flex items-center gap-2 font-bold text-foreground">
          <BookOpen class="size-4 text-primary" aria-hidden="true" />
          Sources
        </h3>
        <SourceList class="mt-3" :sources="subject.sources" :source-types="sourceTypes" />
      </div>
    </Card>
  </section>
</template>
