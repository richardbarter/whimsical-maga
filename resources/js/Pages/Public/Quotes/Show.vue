<script setup lang="ts">
import { Badge } from "@/Components/ui/badge";
import { Card } from "@/Components/ui/card";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import { formatDate, truncate } from "@/lib/utils";
import type { QuotePageProps, QuotePageSubject } from "@/types";
import { Head } from "@inertiajs/vue3";
import { ArrowDown, BookOpen, CalendarDays, Eye, MapPin } from "lucide-vue-next";
import { computed } from "vue";
import ClaimRealityCheck from "./components/ClaimRealityCheck.vue";
import SourceList from "./components/SourceList.vue";
import SubjectSection from "./components/SubjectSection.vue";

const props = defineProps<QuotePageProps>();

const pageTitle = computed(() => `"${truncate(props.quote.text, 60)}"`);

/** Spoken is the default and goes unlabelled; Alleged, Paraphrased etc. change how the quote reads. */
const quoteTypeLabel = computed(() => {
  const { quote_type: type, quote_type_note: note } = props.quote;
  if (!type || type === "spoken") return null;
  if (type === "other" && note) return note;

  return props.quoteTypes.find((option) => option.value === type)?.label ?? null;
});

const speakerInitials = computed(() =>
  (props.quote.speaker?.name ?? "")
    .split(/\s+/)
    .filter(Boolean)
    .map((word) => word[0])
    .slice(0, 2)
    .join("")
    .toUpperCase(),
);

const hasTopics = computed(
  () => !!props.quote.categories?.length || !!props.quote.tags?.length,
);

/** Prefixed so a subject slug can never clash with another id on the page. */
function subjectAnchor(subject: QuotePageSubject): string {
  return `subject-${subject.slug}`;
}
</script>

<template>
  <Head :title="pageTitle" />

  <PublicPageLayout>
    <div
      v-if="isPreview"
      role="status"
      class="flex items-start gap-3 rounded-xl border border-accent bg-accent/60 px-4 py-3 text-sm text-accent-foreground"
    >
      <Eye class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
      <p>
        <span class="font-bold">Draft preview, not public.</span>
        This quote is {{ quote.status }}, so only admins can see this page.
      </p>
    </div>

    <h1 class="sr-only">
      Quote<template v-if="quote.speaker"> by {{ quote.speaker.name }}</template>
    </h1>

    <!-- The quote -->
    <Card class="p-6 sm:p-10">
      <figure>
        <span
          class="block select-none font-serif text-7xl leading-none text-primary"
          aria-hidden="true"
          >&ldquo;</span
        >
        <blockquote class="-mt-6">
          <p class="text-2xl font-semibold leading-snug text-foreground sm:text-3xl">
            {{ quote.text }}
          </p>
        </blockquote>

        <figcaption class="mt-8 flex items-center gap-3">
          <span
            v-if="speakerInitials"
            class="flex size-11 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-bold text-secondary-foreground"
            aria-hidden="true"
          >
            {{ speakerInitials }}
          </span>
          <div class="flex min-w-0 flex-col gap-1">
            <p v-if="quote.speaker" class="font-bold text-foreground">
              {{ quote.speaker.name }}
            </p>
            <div
              v-if="quote.occurred_at || quote.location || quoteTypeLabel"
              class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
            >
              <span v-if="quote.occurred_at" class="inline-flex items-center gap-1.5">
                <CalendarDays class="size-3.5 shrink-0" aria-hidden="true" />
                <time :datetime="quote.occurred_at">{{ formatDate(quote.occurred_at) }}</time>
              </span>
              <span v-if="quote.location" class="inline-flex items-center gap-1.5">
                <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                {{ quote.location }}
              </span>
              <Badge
                v-if="quoteTypeLabel"
                class="border-transparent bg-accent text-accent-foreground"
              >
                {{ quoteTypeLabel }}
              </Badge>
            </div>
          </div>
        </figcaption>
      </figure>

      <div v-if="quote.context" class="mt-8 border-t border-border pt-6">
        <h2 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
          Context
        </h2>
        <p class="mt-2 whitespace-pre-line leading-relaxed text-foreground/85">
          {{ quote.context }}
        </p>
      </div>
    </Card>

    <ClaimRealityCheck :claim="quote.claim" :reality-check="quote.reality_check" />

    <nav
      v-if="subjects.length >= 2"
      aria-labelledby="on-this-page"
      class="rounded-xl border border-border bg-card px-6 py-5 shadow-sm"
    >
      <h2 id="on-this-page" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
        On this page
      </h2>
      <ol class="mt-3 flex flex-col gap-2">
        <li v-for="subject in subjects" :key="subject.id">
          <a
            :href="`#${subjectAnchor(subject)}`"
            class="inline-flex items-start gap-2 font-semibold text-foreground transition-colors hover:text-primary"
          >
            <ArrowDown class="mt-1 size-4 shrink-0 text-primary" aria-hidden="true" />
            {{ subject.name }}
          </a>
        </li>
      </ol>
    </nav>

    <SubjectSection
      v-for="subject in subjects"
      :key="subject.id"
      :subject="subject"
      :anchor-id="subjectAnchor(subject)"
      :source-types="subjectSourceTypes"
    />

    <Card v-if="quote.sources?.length" class="p-6 sm:p-8">
      <h2 class="flex items-center gap-2 text-lg font-bold text-foreground">
        <BookOpen class="size-5 text-primary" aria-hidden="true" />
        Sources
      </h2>
      <SourceList class="mt-4" :sources="quote.sources" :source-types="sourceTypes" />
    </Card>

    <section v-if="hasTopics">
      <h2 class="sr-only">Categories and tags</h2>
      <ul class="flex flex-wrap gap-2">
        <li v-for="category in quote.categories" :key="`category-${category.id}`">
          <Badge variant="secondary">{{ category.name }}</Badge>
        </li>
        <li v-for="tag in quote.tags" :key="`tag-${tag.id}`">
          <Badge variant="outline" class="bg-card font-medium text-muted-foreground">
            #{{ tag.name }}
          </Badge>
        </li>
      </ul>
    </section>
  </PublicPageLayout>
</template>
