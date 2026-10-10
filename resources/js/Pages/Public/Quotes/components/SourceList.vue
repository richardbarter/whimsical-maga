<script setup lang="ts">
import type { SelectOption } from "@/types";
import { ExternalLink } from "lucide-vue-next";

/** Plain source fields, shared by quote sources and subject sources. */
interface SourceListItem {
  url: string;
  title?: string | null;
  source_type?: string | null;
  archived_url?: string | null;
}

const LINK_REL = "nofollow noopener noreferrer";

const props = defineProps<{
  sources: SourceListItem[];
  sourceTypes: SelectOption[];
}>();

function linkText(source: SourceListItem): string {
  if (source.title) return source.title;

  try {
    return new URL(source.url).hostname.replace(/^www\./, "");
  } catch {
    return source.url;
  }
}

function typeLabel(value?: string | null): string | undefined {
  return props.sourceTypes.find((type) => type.value === value)?.label;
}
</script>

<template>
  <ol class="flex list-decimal flex-col gap-3 pl-5 text-foreground marker:font-bold marker:text-primary">
    <li v-for="(source, index) in sources" :key="index" class="break-words pl-1">
      <a
        :href="source.url"
        target="_blank"
        :rel="LINK_REL"
        class="font-semibold underline decoration-primary/40 decoration-2 underline-offset-4 transition-colors hover:text-primary hover:decoration-primary"
      >
        {{ linkText(source) }}<ExternalLink class="ml-1 inline size-3.5 align-baseline text-muted-foreground" aria-hidden="true" />
      </a>
      <span v-if="typeLabel(source.source_type)" class="text-sm text-muted-foreground">
        · {{ typeLabel(source.source_type) }}
      </span>
      <span v-if="source.archived_url" class="text-sm text-muted-foreground">
        ·
        <a
          :href="source.archived_url"
          target="_blank"
          :rel="LINK_REL"
          class="underline decoration-border underline-offset-4 transition-colors hover:text-foreground hover:decoration-foreground"
        >
          archived copy
        </a>
      </span>
    </li>
  </ol>
</template>
