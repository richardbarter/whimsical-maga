# Quote Full Details & Subjects: Implementation Plan

> **Status:** Complete. Steps 1–7 were done on 2026-09-27 and committed on 2026-10-10. The "Later (Out of Scope)" and "Refactoring Candidates" sections are still open. Written 2026-09-26, decisions updated 2026-09-27, on branch `feat/quote-full-details` (from `main` at `8382632`).
> **Blocked on:** nothing. All decisions are made (see the [Decisions Log](#decisions-log)).

## How to Use This Doc (for Claude)

1. Follow `CLAUDE.md` and the memory notes:
   - controllers follow Authorize → Validate → Act → Respond;
   - Form Requests, services and route model binding;
   - tests for every change;
   - `vendor/bin/pint --dirty` and `npm run build`;
   - commit only when asked;
   - never run `fly deploy`;
   - never commit `docs/screenshots/`.
2. The dependencies listed under [Decisions Made](#decisions-made) are **approved**. Install each one only in the step that needs it. Anything else still needs approval.
3. Work through [Verify Before Starting](#verify-before-starting).
4. Check the [Decisions Log](#decisions-log). If a decision a step needs is still open, ask the user before starting that step.
5. Do **one step at a time**. After each one:
   - run its tests, pint and the build;
   - summarise;
   - mention refactoring candidates without doing them;
   - tick the step here.
6. When the feature's tests pass, ask whether to run the full suite.

## The Concept

Three levels of detail:

1. **Quote text:** what was said.
2. **Context:** a sentence or two about the situation. Shown on the home page rotation card.
3. **Full details:** a long, formatted explanation of a **subject** (e.g. "Mail-in ballots are rife with fraud"). Shown on the quote's own page.

Full details belong to a reusable **Subject**, not to a single quote, because many quotes make the same argument. A quote can link to **several** subjects. A subject used by only one quote works the same way.

**The Skeptical Science model** (skepticalscience.com: myth → "what the science says" → detailed page) maps onto this:
- the subject name is the myth;
- a short answer comes later (`summary`, with the Arguments tab);
- `full_details` is the detailed page.

The per-quote `claim` and `reality_check` stay on the quote, as the short rebuttal of that specific quote.

**Writing guideline:** full details should be general, because other quotes will share them. Anything specific to one quote (the rally, the date, the reaction) goes in that quote's `context` or `reality_check`.

**Arguments tab (later, not in this plan):** `NavMenu` already shows "Arguments (Coming Soon)". It will list subjects, each with its own page (subject › full details › "Quotes on this subject"), reusing `SubjectSection.vue` and `RichTextContent.vue` from Step 4.

## Current State (verified 2026-09-26)

- **`Quote`** (`app/Models/Quote.php`):
  - fields: `text`, `context` (`max:2000`), `claim` and `reality_check` (max 10,000, **not shown publicly anywhere yet**), `location`, `occurred_at`, `status` (`QuoteStatus`), `quote_type`, `is_verified`, `is_featured`, `view_count`, `slug` (unique), `speaker_id`;
  - relations: `tags`, `categories`, `sources`;
  - `scopePublished()` and `scopeFeatured()`; soft deletes.
- **Slugs:** `QuoteService::update()` regenerates the slug whenever the text changes. That stays; the URL design below handles it.
- **Admin:**
  - `Route::resource('quotes')->except('show')`, plus toggle routes;
  - `Admin\QuoteController` (with `formOptions()`), `app/Http/Requests/Admin/QuoteRequest.php`;
  - `QuoteService`, whose `syncRelations()` syncs tags and categories via `ResolvesByName::resolveIds()`;
  - `Pages/Admin/Quotes/components/QuoteForm.vue`.
- **Pattern to mirror for a new admin section:** saved contexts (`SavedContextController`, `SavedContextService`, `SavedContextRequest`, `Pages/Admin/SavedContexts/*`, `tests/Feature/Admin/SavedContextTest.php`).
- **Helpers:**
  - `app/Models/Concerns/HasUniqueSlug.php`: checks soft-deleted rows too, collisions get `-1`, `-2`, …;
  - `app/Models/Concerns/ResolvesByName.php`: `{id, name}` combobox items, with a case-insensitive find-or-create on the `name` column;
  - `Tag` sets its slug in `booted()` on creating.
- **UI components:**
  - `Components/ui/combobox-multi-select/ComboboxMultiSelect.vue` handles multi-select with inline create, as used for tags and categories;
  - `Components/ui/` also has button, dialog, dropdown-menu, popover, tooltip, input and separator, which is enough for the editor toolbar. It has no toggle component.
- **Public side:**
  - only `Pages/Public/Home.vue` exists (`PublicLayout`, `NavMenu`);
  - `QuoteFeedService::batch()` selects `id, text, context, occurred_at, is_featured, speaker_id`;
  - rotation lives in `composables/useQuoteRotation.ts` (with tests in `resources/js/__tests__/composables/useQuoteRotation.test.ts`);
  - `resources/js/app.ts` lazy-loads each page (`import.meta.glob`, not eager), so editor code only loads on the admin pages that use it.
- **No Inertia SSR.** `<Head>` tags only exist after JavaScript runs, so link-preview bots don't see them. `resources/views/app.blade.php` is the place for sharing tags.
- **CSP** (`app/Csp/AppPreset.php`):
  - script nonces;
  - `img-src 'self' data: blob: https://*.r2.dev`, so hot-linked external images are blocked;
  - `style-src` allows inline styles.
- **Tailwind v4:** CSS-first, in `resources/css/app.css` (`@import "tailwindcss";`). Plugins are added with `@plugin`.
- **Markdown:** `league/commonmark` 2.10 is installed (Laravel requires it), which gives `Str::markdown()`. It isn't used for full details, but it's handy for turning AI-drafted Markdown into HTML later.
- **Images today:**
  - backgrounds upload through `BackgroundService` (`storeImage()` strips metadata with `ImageMetadataStripper`, then stores the file);
  - validation lives in `BackgroundRequest` (`image`, `mimes:jpg,jpeg,png,webp`, `max:10240`);
  - `Background::url()` uses `Storage::disk()->url()`;
  - production disk is R2 (`fly.toml`: `FILESYSTEM_DISK = 'r2'`); local development uses `local`.
- **Database:** PostgreSQL in development and production, SQLite in memory for tests. Production has real data.

## Decisions Made

| Topic | Decision | Why |
|---|---|---|
| Where full details live | New `Subject` model: `name`, `slug`, `full_details`, `status`. Quote ↔ Subject is many-to-many with a `position` (the admin's order) | Explanations are shared across quotes and become the Arguments pages later |
| Column name | `name` (labelled "Title" in the UI) | Reuses `ResolvesByName` and `ComboboxItem {id, name}` unchanged |
| Subject status | `SubjectStatus` enum (Draft, Published) | An unfinished explanation mustn't go live when attached to a published quote |
| New subjects from the quote form | Yes, typed by title like tags. Created as a **draft** with empty full details | Fast while entering quotes; drafts stay hidden |
| **Editor** | **TipTap** (free, MIT) with a toolbar built from our shadcn components | Formatting shows as you type, and pasting from Word or Google Docs keeps bold, lists, headings, links and tables |
| **Storage format** | **HTML, cleaned on the server** with `symfony/html-sanitizer` (an allow-list), **on save and again on display** | It's what TipTap produces and what the browser shows. It supports tables with merged cells and image captions, which Markdown can't hold. It needs no conversion step |
| **Tables** | Yes, with TipTap's free table extension | Needed for comparisons and timelines |
| **Images** | Uploaded to our storage (R2) from the editor, with alt text required. Images inside pasted documents are stripped | The CSP blocks external images, and pasted Google/Word image links are temporary or local files |
| **Typography** | `@tailwindcss/typography` (`prose` classes) on both the editor and the public page | What you see while writing matches the public page |
| **Subject sources** | A structured Sources list per subject (as many as needed), plus links inside the text via the editor's link button | Subject sources are the evidence behind statements in the full details (e.g. the study behind "there's a scientific consensus on man-made climate change"), kept in one place with archived copies |
| **Subject source types** | Their own `SubjectSourceType` enum, with no "Primary source" switch | Quote source types describe where something was said; evidence needs different types |
| Quote page layout | Quote › speaker, date, location, type › context › claim & reality check › "On this page" (2+ subjects) › each subject's title and full details › sources › tags and categories | Agreed layout |
| URLs | `/quotes/{id}/{slug}`. Found by ID; a wrong or missing slug gets a **301** to the current URL | The slug always matches the text and shared links never break |
| Who sees what | Published quotes: everyone. Drafts: **admins only**, with a banner or badge. Everyone else gets a **404** (`denyAsNotFound`) | Admin preview without revealing drafts |
| Home link | "Full details →" on the home card, **only** when the quote has at least one published subject | The link promises detail, so it only appears when there is some |
| Home feed | Stays lean: adds `slug` and a `has_full_details` boolean only | Performance |
| Length | `full_details` max **150,000** characters of stored HTML | About 100,000 characters of visible text, the originally agreed limit, since HTML tags add overhead |
| Sharing tags | Rendered server-side in Blade via `Inertia::render()->withViewData('meta', …)` | There's no SSR |
| Quotes table | **Unchanged.** Everything new is in new tables | Nothing to migrate on production quotes |
| `view_count` | Out of scope (see [Later](#later-out-of-scope)) | Its own task |
| **Approved dependencies** (2026-09-27) | npm: `@tiptap/vue-3`, `@tiptap/pm`, `@tiptap/starter-kit`, `@tiptap/extension-table`, `@tiptap/extension-image`. npm dev: `@tailwindcss/typography`. Composer: `symfony/html-sanitizer` | All free (MIT). TipTap's paid add-ons (collaboration, AI, document import) aren't needed |

## Editor and Content Rules

These rules are shared by the editor (Step 3), the sanitizer (Step 3) and the public display (Step 4). Keep all three in sync: anything the editor can produce, the sanitizer must allow, and anything else is removed.

| Content | Editor | Stored / allowed HTML |
|---|---|---|
| Paragraphs, line breaks | default | `p`, `br` |
| Bold, italic, strikethrough | toolbar and shortcuts | `strong`, `em`, `s` |
| Headings | "Heading" and "Subheading" buttons | `h3`, `h4` only. The subject title is the `h2` on the quote page |
| Bullet and numbered lists | toolbar | `ul`, `ol`, `li` |
| Quote block, divider | toolbar | `blockquote`, `hr` |
| Links | toolbar popover (URL input), never `window.prompt` | `a[href]` with http, https or mailto only; `rel="nofollow noopener noreferrer"` and `target="_blank"` forced by the sanitizer |
| Tables | table menu: insert, add/remove rows and columns, header row, merge/split cells, delete table | `table`, `thead`, `tbody`, `tr`, `th[colspan,rowspan]`, `td[colspan,rowspan]`; `colgroup` and `col` without styles |
| Images (Step 6) | "Insert image" dialog, or paste/drop an image file | `figure`, `img[src,alt,width,height]` from our storage host only, `figcaption`; `loading="lazy"` forced |
| **Removed** | not in the editor | everything else: `script`, `style`, `iframe`, `object`, `embed` (with their contents), every `style`, `class` and `on*` attribute, and fonts, colours, `span` and `div` wrappers (text kept) |

**Rules for the editor:**
- **StarterKit:** turn off code, code block and underline (underline looks like a link on the web). Replace the built-in heading with an extended `Heading` limited to levels 3 and 4, whose `parseHTML` maps pasted `h1`/`h2`/`h3` to level 3 and `h4`/`h5`/`h6` to level 4, so pasted headings aren't lost.
- **Pasting:** TipTap keeps only what the schema allows, which is exactly the table above.
  - **Google Docs** wraps pasted content in `<b style="font-weight:normal">`. TipTap's Bold should ignore it; test it.
  - **Word:** pasting from Word is a bonus, not a requirement (user, 2026-09-27). Word puts bullet lists on the clipboard as `mso-list` paragraphs, so they may paste as plain paragraphs. That's acceptable. Don't add a paste handler for it unless the user asks.
- **Empty content:** an empty editor (`<p></p>`) is saved as `null`.

**Sanitizer** (`app/Services/RichTextSanitizer.php`, wrapping Symfony's `HtmlSanitizer`):
- `allowElement()` for each tag in the table, with only the listed attributes;
- `allowLinkSchemes(['http', 'https', 'mailto'])`;
- `forceAttribute('a', 'rel', 'nofollow noopener noreferrer')` and `forceAttribute('a', 'target', '_blank')`;
- `dropElement()` for `script`, `style`, `iframe`, `object` and `embed`;
- keep the text of unknown wrapper elements (`span`, `div`, `font`, `b`, `i`, `u`) with `blockElement()`, or with a default action of Block if the installed version supports it;
- Step 6 adds `allowMediaSchemes(['https'])`, `allowMediaHosts([...storage host])` and `forceAttribute('img', 'loading', 'lazy')`;
- confirm the exact method names against the installed version.

## Open Decisions

None as of 2026-09-27. If a new question comes up during implementation, ask the user and record the answer in the [Decisions Log](#decisions-log).

## Implementation Steps

### Step 1: Public quote page without subjects ✅ (done 2026-09-27)

> **As built, differences from the spec below:**
> - The route also has `->whereNumber('quote')`, so a non-numeric ID is a 404 rather than a PostgreSQL type error.
> - `QuotePageService::load()` takes only the quote for now. **Step 4 adds the `?User` parameter** for `visibleTo()`.
> - An extra `quoteTypes` prop (`QuoteType::options()`) supplies the quote type label, so no list is hardcoded in Vue. `other` shows `quote_type_note` when set.
> - `user_id` is hidden from the `quote` prop.
> - Vitest tests were added for `SourceList` and `ClaimRealityCheck` (`resources/js/__tests__/components/`).

- **Policy:** `app/Policies/QuotePolicy.php`, `view(?User $user, Quote $quote): Response`.
  - Allow when `status === Published` or `$user?->isAdmin()`; otherwise `Response::denyAsNotFound()`.
  - The `?User` type hint is required for guests to reach the policy.
- **Route** (public section of `routes/web.php`):
  - `Route::get('/quotes/{quote}/{slug?}', [QuotePageController::class, 'show'])->can('view', 'quote')->name('quotes.show')`;
  - binding is by ID, and soft-deleted quotes are a 404 automatically.
- **Controller:** `app/Http/Controllers/QuotePageController.php`, named to avoid clashing with `Admin\QuoteController` in `routes/web.php`.
  - `show(Request $request, Quote $quote, ?string $slug = null)`: if `$slug !== $quote->slug`, 301 to `route('quotes.show', [$quote, $quote->slug])`.
  - Otherwise, **Act:** `$this->quotePage->load($quote, $request->user())`. **Respond:** `Inertia::render('Public/Quotes/Show', [...])->withViewData('meta', $this->quotePage->meta($quote))`.
- **Service:** `app/Services/QuotePageService.php`.
  - `load()` eager-loads `speaker:id,name`, `sources` (primary first: `orderByDesc('is_primary')`), `tags:id,name,slug` and `categories:id,name,slug,color`.
  - `meta()` returns:
    - `title`: `“{Str::limit(text, 60)}” — {speaker}`;
    - `description`: context, else `Str::limit(reality_check, 160)`, else `config('app.name')`;
    - `url`: the canonical route.
- **Props:** `quote`, `isPreview` (`status !== Published`, which only an admin can reach) and `sourceTypes` (`SourceType::options()`, for type labels).
- **Blade** (`resources/views/app.blade.php` head, inside `@isset($meta)`):
  - `<meta name="description">`;
  - `og:title`, `og:description`, `og:url`, `og:type=article`, `og:site_name`;
  - `twitter:card=summary`;
  - `<link rel="canonical">`.
  - Use `{{ }}` escaping only.
- **Frontend:**
  - `resources/js/Pages/Public/Quotes/Show.vue` uses `PublicLayout` and `<Head :title>`, with a readable single column, a back-to-home `Link`, and a "Draft preview, not public" banner when `isPreview`. It shows the quote type when it isn't `spoken` (Alleged and Paraphrased matter).
  - Co-located components in `Pages/Public/Quotes/components/`: `ClaimRealityCheck.vue` (side by side, stacking on mobile; plain text with `whitespace-pre-line`; hidden when both are empty) and `SourceList.vue`.
    - `SourceList.vue` is a numbered list: the title (or domain) as the link, the type label, and an "archived copy" link.
    - It takes plain `{url, title, source_type, archived_url}` items plus the type options, so Step 7 can reuse it for subject sources.
  - Match the look of the home card (white card, rounded), with no dark mode unless public pages gain it.
- **Admin:** a "View page" link on `Pages/Admin/Quotes/Edit.vue` (new tab).
- **Types:** add `QuotePageProps` to `resources/js/types/index.d.ts`.
- **Tests** (`tests/Feature/QuotePageTest.php`):
  - a guest sees a published quote (component and props);
  - draft and pending quotes are a 404 for guests and for logged-in non-admins;
  - an admin sees a draft with `isPreview` true;
  - trashed and unknown IDs are a 404;
  - a missing or outdated slug gets a 301 to the canonical URL;
  - after the text is edited, the old URL 301s to the new slug;
  - the full HTML response contains the OG tags, and quote text containing `"<script>` comes out escaped;
  - sources are ordered primary first.

### Step 2: Subjects (data, admin CRUD with name and status, quote form picker) ✅ (done 2026-09-27)

> **As built, differences from the spec below:**
> - The pivot migration is timestamped one second after `create_subjects_table` so it runs second (artisan gave both the same timestamp, and alphabetical order would have run the pivot first).
> - `Subject` has `$attributes = ['status' => 'draft']`, so a subject created by name from the quote form is a draft in memory as well as in the database.
> - The Subjects index uses an explicit `select()` before `withCount('quotes')`, so `full_details` isn't sent for every row. `withCount()` sets its own select, so the column list passed to `paginate()` would have been ignored. The same gotcha appears in Step 5.
> - `Admin\QuoteController::edit()` loads `subjects:id,name` (not the full subject), so the quote form doesn't receive `full_details`.
> - `SubjectRequest` labels `name` as "title" in error messages, to match the form.
> - The Subjects card sits after "Claim & Reality Check" on the quote form.
> - Both migrations have been run on the local Docker database.
> - Added afterwards:
>   - `SubjectSeeder`, for development only. Run it with `php artisan db:seed --class=SubjectSeeder`; it isn't in `DatabaseSeeder`.
>   - Factory states `withRichDetails()` (every element in the content rules, useful for Steps 3–4) and `withoutDetails()`.
>   - A shared `Components/ui/status-badge/StatusBadge.vue`, now used by the quote and subject tables.
>   - The admin forms now call Inertia's `form.submit(method, url)` instead of each having its own post/put `if`/`else`.
>   - `/admin/subjects` pages and the public quote page added to the e2e smoke test.

The `full_details` column is created here, but its editor arrives in Step 3.

- **Migrations** (new tables):
  - `subjects`: `id`, `name` (string, indexed), `slug` (unique), `full_details` (text, nullable), `status` (string, default `draft`, indexed), timestamps and soft deletes;
  - `quote_subject`: `quote_id` and `subject_id` (`constrained()->cascadeOnDelete()`), `position` (`unsignedSmallInteger`, default 0), and a unique index on (`quote_id`, `subject_id`). No timestamps, like `quote_tag`.
- **Enum:** `app/Enums/SubjectStatus.php` (Draft, Published) with `label()` and `options()`, copied from `QuoteStatus`.
- **Model:** `app/Models/Subject.php`.
  - Uses `HasFactory`, `HasUniqueSlug`, `ResolvesByName`, `SoftDeletes`.
  - Fillable: `name`, `slug`, `full_details`, `status`; status is cast to the enum.
  - The slug is set in `booted()` on creating, like `Tag`.
  - Relations and scopes: `quotes()` belongsToMany; `scopePublished()`; `scopeVisibleTo(Builder, ?User)` (everything for admins, published only otherwise).
- **On `Quote`:** `subjects()` belongsToMany with `->withPivot('position')->orderByPivot('position')`.
- **Factory:** `database/factories/SubjectFactory.php` with a `published()` state. `full_details` should be simple HTML, e.g. `<p>…</p>`.
- **Admin CRUD** (mirroring saved contexts):
  - `Route::resource('subjects', SubjectController::class)->except('show')` under `/admin`;
  - `app/Http/Requests/Admin/SubjectRequest.php`: `name` required|string|max:255; `status` required with `Rule::enum(SubjectStatus::class)`;
  - `app/Services/SubjectService.php`: create and update, regenerating the slug when the name changes (the same approach as `QuoteService`);
  - `Pages/Admin/Subjects/Index.vue`: name, status badge, quote count (`withCount('quotes')`), last updated, edit/delete (`useDeleteConfirmation`);
  - `Create.vue`, `Edit.vue` and `components/SubjectForm.vue`, with name and status fields;
  - status options passed as a prop from `SubjectStatus::options()`;
  - add `{ label: 'Subjects', routeName: 'admin.subjects.index', pattern: 'admin.subjects.*' }` to the nav list in `resources/js/Layouts/AdminLayout.vue`.
- **Quote form:**
  - `QuoteRequest` adds `subjects` (nullable array), `subjects.*.id` (nullable integer, `Rule::exists('subjects', 'id')->whereNull('deleted_at')`) and `subjects.*.name` (required|string|max:255), plus attributes and messages like tags;
  - `QuoteService::syncRelations()`: `$quote->subjects()->sync(collect(Subject::resolveIds($data['subjects'] ?? null))->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]))`;
  - `Admin\QuoteController::formOptions()` adds `subjects` (`Subject::orderBy('name')->get(['id','name','slug','status'])`), and `edit()` loads `subjects`;
  - `QuoteForm.vue` gets a "Subjects" card with `ComboboxMultiSelect` ("Select or create subjects…") and help text: "New subjects are created as drafts; write their full details on the Subjects page."
  - Map `initialValues.subjects` on `Pages/Admin/Quotes/Edit.vue`, as it does for tags.
- **Types:** `Subject`, `SubjectFormData`, `QuoteFormData.subjects: ComboboxItem[]`, `Quote.subjects?: Subject[]`.
- **Docs:** add both tables to `docs/database-erd.md`.
- **Tests:**
  - `tests/Feature/Admin/SubjectTest.php` (mirroring `SavedContextTest`):
    - a guest gets a 302 to login and a non-admin a 403 on every route;
    - index, create, store, edit, update and destroy (soft delete);
    - validation: name required, invalid status;
    - the slug is generated, gets a suffix on collision (including trashed rows) and is regenerated on rename.
  - Additions to `tests/Feature/Admin/QuoteTest.php`:
    - subjects sync with positions in the submitted order;
    - a new name creates a **draft** subject;
    - an existing subject is reused case-insensitively;
    - nonexistent or trashed IDs give a validation error;
    - the edit page includes the quote's subjects and the subject options.

### Step 3: Rich text editor and sanitizer (the full details field) ✅ (done 2026-09-27)

> **As built, differences from the spec below:**
> - **Installed versions:** TipTap 3.31, `@tailwindcss/typography` 0.5, `symfony/html-sanitizer` 8.1.
> - **StarterKit v3 includes Link.** It's configured in `extensions.ts` with `isAllowedUri` limited to http, https and mailto. `normalizeLinkUrl()` adds `https://` when no scheme is typed.
> - **Headings:** there's no extended `Heading`, because importing `@tiptap/extension-heading` would add an unapproved dependency (it's only present through StarterKit). StarterKit is set to `levels: [3, 4]`, and `transformPastedHTML` runs `normalizePastedHeadings()` (h1–h2 → h3, h5–h6 → h4). The server sanitizer maps headings the same way, so imported content keeps its structure.
> - **Sanitizer, default action:** it's **Drop**, with an explicit list of unwrapped elements (`blockElement()`: span, div, font, b, i, u, code, pre, sup and so on). A default of Block was tried and rejected: in body context `html-sanitizer` ignores drop rules for head elements (`style`, `title`), so their text leaked into the output.
> - **Sanitizer, input length:** it cuts input at **20,000 bytes** by default. It's raised to `MAX_LENGTH × 4` bytes, and `RichTextSanitizer::MAX_LENGTH` (150,000) is also the validation limit.
> - **Sanitizer, output details:** it trims trailing empty paragraphs, which TipTap's trailing node adds after a final list, heading or table. Void tags come out as `<br />` and `<hr />`, and `@` in mailto links is encoded as `&#64;`.
> - **Typography:** it uses `prose prose-stone` (a warm grey matching the theme) instead of `prose-slate`. **Step 4's `RichTextContent` should use `prose-stone` too.**
> - **Toolbar:**
>   - Buttons use `@mousedown.prevent`, so the editor keeps focus and its selection.
>   - The Link and Table tooltips wrap the Popover and DropdownMenu, not the other way round. All three use reka-ui's Popper, and a trigger anchors to the nearest one, so a tooltip inside a popover leaves the popover unpositioned (off-screen).
> - **Editor lifecycle:** `useEditor` already destroys the editor on unmount, so there's no separate `onBeforeUnmount`.
> - **Bundle:** TipTap is only in the Subjects form chunk (about 440 KB, 140 KB gzipped). `app.js` is unchanged.
> - **Tests:**
>   - `tests/Unit/RichTextSanitizerTest.php`;
>   - `SubjectTest` additions;
>   - Vitest `richTextExtensions.test.ts` (schema plus ProseMirror's real paste parsing) and `RichTextEditor.test.ts` (v-model);
>   - Playwright `tests/e2e/rich-text-editor.spec.ts` (toolbar, link, tables and merging, a Google Docs paste, no form submit).
> - **Docker:** `composer install` was run in the app container and the Vite container was restarted for the new packages.
> - **Manual check still to do by hand:** paste from real Google Docs and Word documents. The e2e test uses Google Docs-style clipboard HTML.

- **Install:**
  - `npm install @tiptap/vue-3 @tiptap/pm @tiptap/starter-kit @tiptap/extension-table`;
  - `npm install -D @tailwindcss/typography`;
  - `composer require symfony/html-sanitizer`.
- **Styles:** add `@plugin "@tailwindcss/typography";` to `resources/css/app.css`.
- **Sanitizer:** `app/Services/RichTextSanitizer.php`, `sanitize(?string $html): ?string`, configured per [Editor and Content Rules](#editor-and-content-rules). It returns `null` when nothing meaningful is left (no text, no table, no image).
- **Saving:**
  - `SubjectRequest` adds `full_details` nullable|string|max:150000;
  - `SubjectService` passes `full_details` through `RichTextSanitizer` before saving (the Act step).
- **Editor:** a shared component in `resources/js/Components/ui/rich-text-editor/`: `RichTextEditor.vue`, `RichTextToolbar.vue` and `index.ts`.
  - It exposes the HTML string through `defineModel<string>()`, and is built with `useEditor` and `EditorContent`.
  - Extensions and paste handling follow the content rules.
  - `editorProps.attributes.class`: `prose prose-slate max-w-none min-h-[300px] …` (matching the shadcn input styles).
  - `onUpdate` emits `editor.getHTML()`.
  - Watch the model and only call `setContent` when it differs, so the cursor doesn't jump.
  - Destroy the editor in `onBeforeUnmount`.
  - **Toolbar:**
    - ghost `Button`s with `aria-pressed` from `editor.isActive()`, and tooltips: Bold, Italic, Strike, Heading, Subheading, bullet list, numbered list, quote block, divider, Link (a `Popover` with an `Input`: set or remove), and undo/redo;
    - a table `DropdownMenu`: insert a 3×3 table with a header row, add rows above/below, add columns before/after, delete a row or column, toggle the header row, merge/split cells, delete the table.
- **Form:** `SubjectForm.vue` uses `RichTextEditor` for `full_details`, with error display.
- **Tests:**
  - `tests/Unit/RichTextSanitizerTest.php`:
    - allowed tags are kept, including a table with `colspan`;
    - `script`, `iframe` and `style` are removed with their contents;
    - `on*`, `style` and `class` attributes are removed;
    - `span`/`div`/`font` wrappers are removed but their text is kept;
    - `javascript:` links lose their `href`;
    - links get `rel` and `target`;
    - `<p></p>` and whitespace become `null`.
  - Additions to `SubjectTest`: store and update save sanitized HTML (a `<script>` payload is stripped); empty editor content is saved as `null`; the 150k limit is enforced.
  - Manual check: paste from Google Docs (bold, headings, lists, links, a table). Also try Word; losing Word's bullet lists is acceptable.

### Step 4: Subjects on the quote page ✅ (done 2026-09-27)

> **As built, differences from the spec below:**
> - **A separate prop:** subjects are a top-level `subjects` prop from `QuotePageService::subjects($quote, $user)`, not eager-loaded onto `quote`. That way the raw `full_details` and the pivot are never sent, and `load()` is unchanged. **Step 7:** add `->with('sources')` in `subjects()` and include the sources in its array.
> - **Anchors:** ids are `subject-{slug}`, prefixed so a slug can't clash with another id on the page.
> - **Shared styles:**
>   - `.rich-text` in `resources/css/app.css` holds cell borders, the header-cell background and link colours;
>   - `RICH_TEXT_CLASSES` (`rich-text prose prose-stone max-w-none`) in `rich-text-editor/utils.ts` is used by both the editor and `RichTextContent`, so what's written matches what's shown;
>   - the editor keeps only its editing-specific CSS.
> - **Keeping TipTap out:** `RichTextContent` is imported from its file directly, not through `rich-text-editor/index.ts`, so the public page doesn't load TipTap. The public page chunk is about 7.7 KB.
> - **Mobile tables:** below 640px, tables get `display: block; overflow-x: auto`, so they scroll within themselves and the page doesn't scroll sideways.
> - **Section content:** each subject has a small "Full details" label above its title. A subject with no full details shows "The full details for this subject haven't been written yet." The draft badge reads "Draft, only admins see this".
> - **Tests:** `QuotePageTest` additions (visibility, order, soft deletes, the exact prop fields, sanitizing on display, null details) and Vitest `SubjectSection.test.ts` (which covers `RichTextContent` too). It was checked in a browser on the seeded data as a guest, as an admin and at phone width, including the anchor links and the Back button.

- `QuotePageService::load()` eager-loads `subjects` constrained by `visibleTo($user)`, in position order.
- Each subject is sent as `{ id, name, slug, status, full_details_html }`, where `full_details_html` has been through `RichTextSanitizer` **again**. This catches content written outside the service (seeders, tinker, a future AI importer) and applies a stricter config to old content if it's tightened later.
- **`Components/ui/rich-text-editor/RichTextContent.vue`:**
  - applies the HTML with `v-html` inside `prose prose-slate max-w-none` (the only place `v-html` is used);
  - makes wide tables scroll sideways on mobile.
- `Pages/Public/Quotes/components/SubjectSection.vue` shows an `<h2 :id="subject.slug">` title, a "Draft" badge (admins only), and `RichTextContent`. Show.vue adds the "On this page" anchor list when there are 2+ subjects.
- **Tests (`QuotePageTest`):**
  - guests only get published subjects, and an admin also gets drafts;
  - subjects are in position order;
  - soft-deleted subjects are hidden;
  - HTML inserted straight into the database with a `<script>`/`onerror` payload comes out sanitized in the props.

### Step 5: Home page link ✅ (done 2026-09-27)

> **As built, differences from the spec below:**
> - **`has_full_details`** also requires `whereNotNull('full_details')`, meaning a published subject whose full details are written. A link that promises detail shouldn't lead to "haven't been written yet".
> - **Hold:**
>   - `useQuoteRotation` takes an `isHeld` ref option; `Home.vue` sets it from hover or keyboard focus inside the card.
>   - Hover uses `pointerenter` with `pointerType === 'mouse'`, because a tap on a touch screen also fires it and would hold the rotation until the next tap elsewhere.
>   - Focus uses `focusin`/`focusout`, with a `relatedTarget` check.
>   - Releasing restarts a full 10-second interval. Pause pressed while held stays paused, and Play pressed while held waits for the release.
> - **Remembered position:**
>   - `useRemember` stores `{ indexes, position }` under the key `Home/quoteRotation`. It's passed a `reactive` object and the result is cast: at runtime reactive in gives reactive out, but the type is `Ref<T> | T`.
>   - A remembered history that doesn't fit the current quote list is discarded during setup, before the first render.
>   - Pause state and the background aren't remembered.
> - **Tests:**
>   - `HomeTest`: the exact field list per quote, `slug`, `has_full_details` true, and 4 false cases (no subjects, draft only, published without details, deleted).
>   - `useQuoteRotation.test.ts`: 4 hold tests and 2 restore tests, using an in-memory `useRemember` stand-in.
>   - Playwright `tests/e2e/home.spec.ts`: "Full details" then Back returns to the same, non-first quote. This needs a real browser because Inertia's history state can't be unit tested.

- **`QuoteFeedService::batch()`:** use `->select(['id','text','context','occurred_at','is_featured','speaker_id','slug'])->withExists(['subjects as has_full_details' => fn ($q) => $q->published()])`, then `->get()` with **no** columns argument.
  - Once `withExists` has set a select, a column list passed to `get()` is ignored and every column gets sent.
- **`Home.vue`:** when `currentQuote.has_full_details`, show a "Full details →" `<Link :href="route('quotes.show', { quote: currentQuote.id, slug: currentQuote.slug })">` under the context line. No `prefetch`.
- **`useQuoteRotation.ts`:**
  - hold the rotation while the card is hovered or focused (a separate `isHeld` flag, so the visitor's own pause/play state isn't changed);
  - keep `history` and `historyPosition` with Inertia's `useRemember`, so pressing Back after visiting a quote page returns to the same quote (check the `useRemember` API with Boost `search-docs`).
- **Types:** `Quote.has_full_details?: boolean`.
- **Tests:**
  - `HomeTest`: quotes include `slug`; `has_full_details` is true with a published subject and false with none or only a draft; quotes don't include `claim`, `reality_check` or `subjects`.
  - `useQuoteRotation.test.ts`: the hold stops auto-advance without toggling `isPaused`, and remembered state is restored.

### Step 6: Images in the editor ✅ (done 2026-09-27)

> **As built, differences from the spec below:**
> - **Figure node:** `rich-text-editor/figure.ts` is a custom **atom** node rather than one built on `@tiptap/extension-image`, which was **not installed**.
>   - The image, alt text and caption are one block, selected, moved and deleted as a unit.
>   - The caption and alt text are edited in the dialog (select the image, then "Edit image"), not inline.
>   - The caption is stored as one text, "Caption — Credit".
>   - To change the image itself, delete it and insert another.
> - **Allowed images:** only URLs starting with `{storage URL}/subject-images/` are kept, on both sides:
>   - the editor gets the `imageUrlPrefix` prop from `SubjectController`;
>   - the server uses `StorageImageSourceSanitizer`, a custom attribute sanitizer, alongside Symfony's media scheme/host check (or relative-only when the storage URL is relative).
>
>   An image from anywhere else is removed together with its figure and caption. The prefix check is stricter than a host check: it rejects our own other folders, lookalike hosts and other ports.
> - **Storage URL forms:**
>   - local: `http://localhost:8000/storage/`;
>   - `Storage::fake()`: relative `/storage/`;
>   - production: `https://pub-….r2.dev/`, which the CSP's `https://*.r2.dev` covers.
>
>   `RichTextSanitizer` takes an optional `$storageUrl` (it defaults to `Storage::disk()->url('')`), so unit tests don't need Laravel.
> - **Pasting and dropping:**
>   - a paste that includes HTML is pasted as text even if it also has an image file, because Word puts a picture of the copied text on the clipboard;
>   - a dropped image file moves the cursor to the drop point before the dialog opens;
>   - on the Add Subject page, pasted or dropped files are ignored, and the Image button is disabled with the tooltip "Save the subject first to add images".
> - **Upload response:** `{id, url, width, height}`. The width and height go on the `<img>` to avoid layout shift. The dialog checks the type and the 10 MB limit before uploading.
> - **`destroy`:** a soft delete with scoped binding. Nothing in the UI uses it yet.
> - **Styles:** shared figure styles are in `.rich-text` in `app.css` (centred, rounded corners, muted caption).
> - **Tests:**
>   - `SubjectImageTest` (14);
>   - `RichTextSanitizerTest` image cases, including lookalike hosts, other folders, other ports, http vs https, `data:` and relative;
>   - `SubjectTest` and `QuotePageTest` image cases;
>   - Vitest figure tests in `richTextExtensions.test.ts`;
>   - Playwright image tests in `rich-text-editor.spec.ts`, which fake the upload with `page.route`, so no data is written.
> - **Local:** the `subject_images` migration has been run. During the browser check, one real test image (a made-up chart) was uploaded and saved into seeded subject 4.

- **Install:** `npm install @tiptap/extension-image`.
- **Migration:** `subject_images`: `subject_id` FK, `file_path`, `alt_text` (**required**), `caption` and `credit` (nullable), `file_size`, `dimensions`, timestamps and soft deletes. Add a `SubjectImage` model and factory, with the file deleted on force delete (as `Background::booted()` does).
- **Upload:**
  - `SubjectImageRequest` uses the rules from `BackgroundRequest` (`image`, `mimes:jpg,jpeg,png,webp`, `max:10240`), plus `alt_text` required and `caption`/`credit` nullable|max:255;
  - `SubjectImageController@store` (`POST /admin/subjects/{subject}/images`, returns JSON `{id, url}`) plus `destroy`;
  - `SubjectImageService` runs `ImageMetadataStripper`, stores to `subject-images/`, and deletes the file if the database insert fails (like `BackgroundService`).
- **Editor:**
  - a small custom `Figure` node (an image plus an editable `figcaption`, with the caption and credit, e.g. "Caption — Credit"), built on `@tiptap/extension-image`;
  - its `parseHTML` only accepts `src` values on our storage host, so images inside pasted documents are dropped;
  - an "Insert image" toolbar button opens a shadcn `Dialog` (file, alt text required, caption, credit), uploads, then inserts the figure;
  - pasting or dropping an image **file** (`handlePaste`/`handleDrop`) opens the same dialog with the file preselected, so alt text is always asked for;
  - only available on the subject **Edit** page (a new subject has no ID yet). On Create, the button is disabled with the tooltip "Save the subject first to add images".
- **Sanitizer:** add `figure`, `figcaption` and `img` per the content rules, with the storage host allowed. Check what `Storage::disk()->url()` returns locally (relative `/storage/…` or absolute) and allow that in development.
- **CSP:** check that the production `CLOUDFLARE_R2_URL` host is covered by `img-src` (currently `https://*.r2.dev`).
- **Tests:**
  - upload is admin-only;
  - validation: type, size, required alt text;
  - metadata is stripped (following the existing `BackgroundTest`/stripper tests);
  - JSON response;
  - the file is deleted on force delete;
  - the sanitizer keeps a storage-hosted `img` inside `figure`, drops an external `img`, and adds `loading="lazy"`.

### Step 7: Subject sources ✅ (done 2026-09-27)

> **As built, differences from the spec below:**
> - **Keeping sources:** as with full details, a subject's sources are left untouched when the request has no `sources` field. An empty list removes them all.
> - **Quote page data:** `QuotePageService::subjects()` eager-loads `sources` and sends each subject's `sources` as `{url, title, source_type, archived_url}`. The page gets `subjectSourceTypes` for the labels.
> - **`SourceList.vue` item type:** it has its own item type that allows `null` title, type and archived URL, instead of `Pick<Source, …>`, whose fields don't allow null.
> - **Section layout:** `SubjectSection` shows a "Sources" heading (`h3`) and the numbered list under the full details, separated by a rule.
> - **The move:** `SourceFormRow` was moved with `git mv` and then unstaged, so nothing is staged. Git still sees a rename at commit time.
> - **Seeder:** `SubjectSeeder` gives each subject that has full details 1–3 ordered sources.
> - **Tests:**
>   - `SubjectTest`: order, wholesale replacement, an empty list, an omitted field, injected fields, and validation of the URL, the 2048-character limit, the type and the archived URL;
>   - `QuotePageTest`: order and fields, and a draft subject's sources hidden from guests;
>   - `SubjectSeederTest`;
>   - Vitest `SourceFormRow.test.ts` and `SubjectSection.test.ts` source cases.
> - **Local:** the migration has been run. Two example sources were added to seeded subject 4 during the browser check.

Subject sources are the evidence behind statements in the full details: the study, report or ruling a claim comes from. For example, "most climate scientists agree humans are causing global warming" is backed by the consensus study. Links inside the text are also possible, using the editor's link button from Step 3.

- **Enum:** `app/Enums/SubjectSourceType.php` with `label()` and `options()`, copied from `SourceType`. The agreed types:

  | Label | Value |
  |---|---|
  | Study / Research | `study` |
  | Government report | `government_report` |
  | Court ruling | `court_ruling` |
  | Official data | `official_data` |
  | News article | `news_article` |
  | Fact-check | `fact_check` |
  | Book | `book` |
  | Video | `video` |
  | Other | `other` |

- **Migration:** `subject_sources`, mirroring `sources` (including the widened URL columns from `2026_09_25_195523_widen_url_columns_on_sources_table.php`), with **no** `is_primary`:
  - `subject_id` (`constrained()->cascadeOnDelete()`);
  - `url` (string, 2048);
  - `title` (nullable);
  - `source_type` (nullable string);
  - `archived_url` (nullable string, 2048);
  - `position` (`unsignedSmallInteger`, default 0);
  - timestamps.
- **Model:**
  - `app/Models/SubjectSource.php`, with `source_type` cast to `SubjectSourceType`, and a factory;
  - `Subject::sources()` is a hasMany ordered by `position`.
- **Validation** (`SubjectRequest`), with attributes like `QuoteRequest` ("source URL" and so on):
  - `sources`: nullable array;
  - `sources.*.url`: required, `url:http,https`, max:2048;
  - `sources.*.title`: nullable|string|max:255;
  - `sources.*.source_type`: nullable, `Rule::enum(SubjectSourceType::class)`;
  - `sources.*.archived_url`: nullable, `url:http,https`, max:2048.
- **Service:** `SubjectService` replaces sources wholesale inside its transaction, like `QuoteService::syncRelations()`, setting `position` from the submitted order.
- **Admin:**
  - Move `Pages/Admin/Quotes/components/SourceFormRow.vue` to `Components/ui/source-form-row/`, since it's now used by two features, and update the import in `QuoteForm.vue`.
  - Add a `showPrimary` prop (default `true`) that hides the "Primary source" switch.
  - Make `is_primary` optional on the `SourceForm` type (or add a separate subject source form type) so subject sources don't send it.
  - `SubjectForm.vue` gets a "Sources" card with "Add source" and the help text "Add the studies, reports and rulings the explanation relies on." It uses the row with `:show-primary="false"` and passes `SubjectSourceType::options()` as `sourceTypes`.
  - `SubjectController`: `edit()` loads `sources`, and `create()`/`edit()` pass `sourceTypes`.
- **Public page:**
  - `QuotePageService` eager-loads `subjects.sources`;
  - `SubjectSection.vue` shows a numbered "Sources" list under the full details, using `SourceList.vue` from Step 1 with `SubjectSourceType::options()` passed as a page prop for the labels;
  - links open in a new tab with `rel="nofollow noopener noreferrer"`.
- **Docs:** add `subject_sources` to `docs/database-erd.md`.
- **Tests:**
  - `SubjectTest`:
    - store and update save sources in the submitted order and replace them wholesale;
    - validation: URL required, http/https only, invalid type, the 2048-character limit.
  - `QuotePageTest`: each subject's sources appear in order, and a draft subject's sources are hidden from guests along with the subject.
  - `resources/js/__tests__/components/SourceFormRow.test.ts`: the Primary switch is hidden when `showPrimary` is false.
  - The existing `QuoteTest` still passes after the move.

## Verify Before Starting

- [x] `git log 8382632..main --oneline` (checked 2026-09-27: no new commits): re-check the files named above for changes, including new migrations and any changes to `QuoteService`, `QuoteFeedService` or `Home.vue`.
- [x] Is PR #37 merged? Yes (2026-09-26), so the AI doc is at `docs/potential/aiResearchAgentIdea.md` on `main`.
- [x] Use Boost `search-docs` to confirm `withViewData`, `useRemember`, guest policies (`?User`), `Response::denyAsNotFound()`, `withExists` aliases and `orderByPivot`. (all confirmed 2026-09-27.)
- [x] **TipTap (current major, v3 at the time of writing):** checked 2026-09-27 (3.31); see Step 3's as-built notes.
  - package names and the StarterKit options (whether Link is included and how to turn off code, code block and underline);
  - the table extension's exports (e.g. `TableKit`);
  - how to extend `Heading.parseHTML`;
  - whether Image is still a separate package;
  - how custom nodes like the `Figure` node are built.
- [x] **`symfony/html-sanitizer`:** checked 2026-09-27 (8.1); see Step 3's as-built notes. A version compatible with PHP 8.4 and Laravel 12, and the exact config methods (`allowElement`, `blockElement`, `dropElement`, `forceAttribute`, `allowLinkSchemes`, `allowMediaHosts`, and the default action for unknown elements).
- [x] **`@tailwindcss/typography`:** the setup for Tailwind v4 (`@plugin`). Done in `resources/css/app.css`.
- [x] **Step 6:** the production `CLOUDFLARE_R2_URL` host vs. the CSP `img-src`, and the local disk's URL format. Checked 2026-10-10: CSP is off (`config/secure-headers.php`), and `RichTextSanitizer` takes the allowed image scheme and host from the default disk's URL, so production uses the R2 host and local uses `http://localhost:8000/storage/`. If `CLOUDFLARE_R2_URL` ever changes (e.g. to a custom domain), images already in full details will be stripped on display until their URLs are updated.

## Later (Out of Scope)

- **`view_count`:**
  - increment once per session per quote, skipping admins;
  - use a base-query increment (`Quote::whereKey($id)->toBase()->increment('view_count')`), because Eloquent's `increment()` also updates `updated_at`;
  - no prefetching on links to quote pages.
- **Subject pages and the Arguments tab:** `/subjects/{id}/{slug}` (subject › full details › quotes on this subject), plus a `summary` column for the Skeptical Science-style list (name = myth, summary = short answer).
- **An `og:image` quote card** for social sharing.
- **AI-drafted full details:** see `docs/potential/aiResearchAgentIdea.md`. The agent can write Markdown, which is converted with `Str::markdown()`, run through `RichTextSanitizer`, and saved as a draft for review in the editor. It never goes straight onto a published subject.
- **Pruning unused subject images** (images removed from the text stay in storage until a clean-up command removes them).
- **Linking saved contexts to subjects** (`docs/potential/contextSaverIdea.md`).
- **A sitemap** for quote pages.

## Refactoring Candidates (Mention, Don't Do)

- `NavMenu.vue` requires the pause button (`isPaused` prop), so it can't be reused on the quote page. Splitting the pause button out would fix that.
- The quote header (text, speaker, date) is shared between the `Home.vue` card and `Show.vue`.
- Image storage could be pulled out of `BackgroundService` for reuse by `SubjectImageService`.

## Decisions Log

| Decision | Choice | Date |
|---|---|---|
| A. Editor & storage format | TipTap, stored as HTML cleaned with `symfony/html-sanitizer` on save and display | 2026-09-27 |
| Tables | Yes, with TipTap's free table extension | 2026-09-27 |
| B. Images | Upload to storage from the editor (`subject_images`), alt text required, pasted external images stripped (Step 6) | 2026-09-27 |
| C. Subject sources | A structured list per subject (`subject_sources`) with its own `SubjectSourceType` list and no Primary switch, plus links in the text via the editor | 2026-09-27 |
| Pasting from Word | A bonus, not required. Losing Word's bullet lists is acceptable | 2026-09-27 |
| D. Typography | `@tailwindcss/typography` | 2026-09-27 |
| Dependencies | Approved: `@tiptap/vue-3`, `@tiptap/pm`, `@tiptap/starter-kit`, `@tiptap/extension-table`, `@tiptap/extension-image`, `@tailwindcss/typography` (dev), `symfony/html-sanitizer` | 2026-09-27 |
