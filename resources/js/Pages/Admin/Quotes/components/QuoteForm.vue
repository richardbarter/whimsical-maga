<script setup lang="ts">
import type {
  Tag,
  Category,
  Speaker,
  QuoteFormData,
  QuoteStatusOption,
  QuoteTypeOption,
  SourceTypeOption,
} from "@/types";
import { Link, router, useForm } from "@inertiajs/vue3";
import { DatePicker } from "@/Components/ui/date-picker";
import { ComboboxMultiSelect } from "@/Components/ui/combobox-multi-select";
import { Button } from "@/Components/ui/button";
import { FormField } from "@/Components/ui/form-field";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Plus } from "lucide-vue-next";
import SourceFormRow from "./SourceFormRow.vue";
import SpeakerAutocomplete from "./SpeakerAutocomplete.vue";
import SavedContextPicker from "./SavedContextPicker.vue";
import SaveContextDialog from "./SaveContextDialog.vue";

const props = defineProps<{
  tags: Tag[];
  categories: Category[];
  speakers: Speaker[];
  quoteTypes: QuoteTypeOption[];
  quoteStatuses: QuoteStatusOption[];
  sourceTypes: SourceTypeOption[];
  initialValues?: Partial<QuoteFormData>;
  submitLabel: string;
  submitMethod: "post" | "put";
  submitRoute: string;
}>();

const form = useForm<QuoteFormData>({
  text: props.initialValues?.text ?? "",
  speaker: props.initialValues?.speaker ?? "",
  quote_type: props.initialValues?.quote_type ?? "",
  quote_type_note: props.initialValues?.quote_type_note ?? "",
  claim: props.initialValues?.claim ?? "",
  reality_check: props.initialValues?.reality_check ?? "",
  context: props.initialValues?.context ?? "",
  location: props.initialValues?.location ?? "",
  occurred_at: props.initialValues?.occurred_at ?? "",
  is_verified: props.initialValues?.is_verified ?? false,
  is_featured: props.initialValues?.is_featured ?? false,
  status: props.initialValues?.status ?? "draft",
  tags: props.initialValues?.tags ?? [],
  categories: props.initialValues?.categories ?? [],
  sources: props.initialValues?.sources ?? [],
});

function addSource() {
  form.sources.push({
    _key: crypto.randomUUID(),
    url: "",
    title: "",
    source_type: "",
    is_primary: false,
    archived_url: "",
  });
}

function removeSource(index: number) {
  form.sources.splice(index, 1);
}

/** The first error for a field or any of its nested items (e.g. "tags.0.id"). */
function firstErrorFor(field: "tags" | "categories"): string | undefined {
  const errors = form.errors as Record<string, string>;
  const key = Object.keys(errors).find((name) => name === field || name.startsWith(`${field}.`));

  return key ? errors[key] : undefined;
}

/** A saved context may have created new tags; refresh the options so they appear here too. */
function refreshTagOptions() {
  router.reload({ only: ["tags"] });
}

function submit() {
  if (props.submitMethod === "post") {
    form.post(props.submitRoute);
  } else {
    form.put(props.submitRoute);
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <!-- Quote Content -->
    <Card>
      <CardHeader>
        <CardTitle>Quote Content</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <FormField label="Quote Text *" for="text" :error="form.errors.text">
          <Textarea
            id="text"
            v-model="form.text"
            placeholder="Enter the quote text..."
            class="min-h-[120px]"
          />
        </FormField>

        <FormField label="Said By *" for="speaker" :error="form.errors.speaker">
          <SpeakerAutocomplete
            v-model="form.speaker"
            input-id="speaker"
            :speakers="speakers"
          />
        </FormField>

        <FormField label="Quote Type *" :error="form.errors.quote_type">
          <Select v-model="form.quote_type">
            <SelectTrigger>
              <SelectValue placeholder="How was this quote delivered?" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="type in quoteTypes"
                :key="type.value"
                :value="type.value"
              >
                {{ type.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormField>

        <FormField
          v-if="form.quote_type === 'other'"
          label="Describe the type"
          for="quote_type_note"
          :error="form.errors.quote_type_note"
        >
          <Input
            id="quote_type_note"
            v-model="form.quote_type_note"
            placeholder="e.g. satirical misquote, composite paraphrase..."
          />
        </FormField>

        <!-- Context has library buttons beside its label, so it lays out its own header -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <Label for="context">Context</Label>
            <div class="flex items-center gap-2">
              <SavedContextPicker @select="form.context = $event" />
              <SaveContextDialog
                :current-body="form.context"
                :tags="tags"
                @saved="refreshTagOptions"
              />
            </div>
          </div>
          <Textarea
            id="context"
            v-model="form.context"
            placeholder="Additional context or explanation..."
            class="min-h-[80px]"
          />
          <p v-if="form.errors.context" class="text-sm text-destructive">
            {{ form.errors.context }}
          </p>
        </div>

        <FormField label="Location" for="location" :error="form.errors.location">
          <Input
            id="location"
            v-model="form.location"
            placeholder="Where was this said? (e.g. White House Press Briefing, Twitter)"
          />
        </FormField>

        <FormField label="Date Occurred" :error="form.errors.occurred_at">
          <DatePicker v-model="form.occurred_at" placeholder="Pick a date" />
        </FormField>
      </CardContent>
    </Card>

    <!-- Claim & Reality Check -->
    <Card>
      <CardHeader>
        <CardTitle>Claim & Reality Check</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <FormField label="Claim" for="claim" :error="form.errors.claim">
          <p class="text-sm text-muted-foreground">
            The assertion or justification being made — what is this quote
            trying to support or prove?
          </p>
          <Textarea
            id="claim"
            v-model="form.claim"
            placeholder="Describe the claim being made..."
            class="min-h-[100px]"
          />
        </FormField>

        <FormField label="Reality Check" for="reality_check" :error="form.errors.reality_check">
          <p class="text-sm text-muted-foreground">
            An analysis of the claim — what is true, what is false, and what is
            missing context?
          </p>
          <Textarea
            id="reality_check"
            v-model="form.reality_check"
            placeholder="Break down what is accurate, misleading, or false..."
            class="min-h-[120px]"
          />
        </FormField>
      </CardContent>
    </Card>

    <!-- Status & Flags -->
    <Card>
      <CardHeader>
        <CardTitle>Status & Flags</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <FormField label="Status *" :error="form.errors.status">
          <Select v-model="form.status">
            <SelectTrigger>
              <SelectValue placeholder="Select status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="status in quoteStatuses"
                :key="status.value"
                :value="status.value"
              >
                {{ status.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormField>

        <div class="flex items-center gap-6">
          <div class="flex items-center gap-2">
            <Switch id="is_verified" v-model="form.is_verified" />
            <Label for="is_verified">Verified</Label>
          </div>

          <div class="flex items-center gap-2">
            <Switch id="is_featured" v-model="form.is_featured" />
            <Label for="is_featured">Featured</Label>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Tags -->
    <Card>
      <CardHeader>
        <CardTitle>Tags</CardTitle>
      </CardHeader>
      <CardContent>
        <ComboboxMultiSelect
          v-model="form.tags"
          :options="tags"
          placeholder="Select or create tags..."
          search-placeholder="Search tags..."
          new-item-placeholder="New tag name..."
          :error="firstErrorFor('tags')"
        />
      </CardContent>
    </Card>

    <!-- Categories -->
    <Card>
      <CardHeader>
        <CardTitle>Categories</CardTitle>
      </CardHeader>
      <CardContent>
        <ComboboxMultiSelect
          v-model="form.categories"
          :options="categories"
          placeholder="Select or create categories..."
          search-placeholder="Search categories..."
          new-item-placeholder="New category name..."
          :error="firstErrorFor('categories')"
        />
      </CardContent>
    </Card>

    <!-- Sources -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Sources</CardTitle>
          <Button type="button" variant="outline" size="sm" @click="addSource">
            <Plus class="mr-1 h-4 w-4" />
            Add Source
          </Button>
        </div>
      </CardHeader>
      <CardContent class="space-y-4">
        <p v-if="!form.sources.length" class="text-sm text-muted-foreground">
          No sources added yet. Click "Add Source" to add one.
        </p>

        <SourceFormRow
          v-for="(source, index) in form.sources"
          :key="source._key"
          v-model:source="form.sources[index]"
          :index="index"
          :source-types="sourceTypes"
          :errors="form.errors as Record<string, string>"
          @remove="removeSource(index)"
        />
      </CardContent>
    </Card>

    <!-- Submit -->
    <div class="flex items-center justify-end gap-3">
      <Link
        :href="route('admin.quotes.index')"
        class="text-sm text-muted-foreground hover:text-foreground"
      >
        Cancel
      </Link>
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? "Saving..." : submitLabel }}
      </Button>
    </div>
  </form>
</template>
