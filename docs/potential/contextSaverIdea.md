# Idea: Connect Quotes to Saved Contexts

## The Problem

Currently, saved contexts are a text template library. When you load one into the quote form, the body text is copied into the `context` textarea. The quote saves that text as a plain string — there is no record of which saved context it came from.

This means there's no way to query: *"which quotes used (or were based on) the Iran Nuclear Deal context?"*

## The Idea

Add a nullable `saved_context_id` foreign key to the `quotes` table. This would record which saved context a quote's context field originated from — purely as a "loaded from" link. The text itself can still be edited freely after loading; the link just tracks provenance.

## Design Note

The FK would mean "this quote originated from that saved context", not "this quote's context text still matches that saved context". If you edit the text after loading, the link stays. That's intentional — it answers the question "which quotes used this context as a starting point?", which is the useful query.

## Required Changes

### Backend

1. **Migration** — add nullable FK to quotes:
   ```php
   $table->foreignId('saved_context_id')->nullable()->constrained()->onDelete('set null');
   ```

2. **`Quote` model** — add relationship:
   ```php
   public function savedContext(): BelongsTo
   {
       return $this->belongsTo(SavedContext::class);
   }
   ```

3. **`SavedContext` model** — add inverse:
   ```php
   public function quotes(): HasMany
   {
       return $this->hasMany(Quote::class);
   }
   ```

4. **`QuoteRequest`** — add validation rule:
   ```php
   'saved_context_id' => ['nullable', 'integer', 'exists:saved_contexts,id'],
   ```

5. **`QuoteController`** — include the field in `store()` and `update()`:
   ```php
   $request->safe()->only([..., 'saved_context_id'])
   ```

6. **`SavedContextController::store()`** — needs to return the new context's ID when called via axios (from the Save Context dialog). Add a JSON response branch:
   ```php
   if ($request->expectsJson()) {
       return response()->json(['id' => $savedContext->id]);
   }
   return redirect()->route('admin.saved-contexts.index')->with('success', '...');
   ```

### Frontend

1. **`QuoteFormData` type** (`resources/js/types/index.d.ts`):
   ```typescript
   saved_context_id: number | null;
   ```

2. **`QuoteForm.vue`** — add to `useForm` initialiser:
   ```typescript
   saved_context_id: props.initialValues?.saved_context_id ?? null,
   ```

3. **`SavedContextPicker.vue`** — emit the context `id` alongside the body text so the form can store both:
   ```typescript
   // Change emit signature
   const emit = defineEmits<{ select: [id: number, body: string] }>()

   // When selecting a result
   emit('select', result.id, result.body)
   ```

   In `QuoteForm.vue`, update the handler:
   ```html
   <SavedContextPicker
       :tags="tags"
       @select="(id, body) => { form.saved_context_id = id; form.context = body }"
   />
   ```

4. **`SaveContextDialog.vue`** — after saving, capture the returned ID and set it on the form. Requires threading an `onSaved` callback or using an emit with the ID:
   ```typescript
   // After axios.post succeeds, emit the new ID
   const data = response.data as { id: number }
   emit('saved', data.id)
   ```

   In `QuoteForm.vue`:
   ```html
   <SaveContextDialog
       :current-body="form.context"
       :tags="tags"
       @saved="(id) => { form.saved_context_id = id }"
   />
   ```

## Estimated Effort

Small — roughly 30 minutes. The only non-trivial part is the `SaveContextDialog` returning the new context ID, which requires the controller to detect `expectsJson()` and return JSON rather than a redirect.
