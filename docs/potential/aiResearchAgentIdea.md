# Idea: AI Research Agent for Quote Details

> **Status:** Idea, not started. Written 2026-09-26.
> Model names, tool versions, prices and Anthropic's policies change often. Re-check the [Verify Before Starting](#verify-before-starting) list before planning.

## The Idea

Add a third level of detail to quotes: **full details**. It would be unlimited length, with its own page per quote, and sit alongside the existing `context`, `claim` and `reality_check` fields.

Then use an AI agent for the research. Given a quote and its main source, it would:

- search for more coverage and read it;
- draft the context, full details and additional sources, following written workflow instructions.

A human (admin) always reviews before anything is published.

## Is This a Good Use Case for an Agent?

Yes.

- **The steps can't be scripted in advance.** Which sources exist, which are worth reading and what's disputed are different for every quote. An agent that searches, reads and decides what to read next handles this; a fixed script doesn't.
- **Mistakes can be caught.** Every draft is reviewed before publishing, which makes the risk acceptable.
- **The main risk is accuracy.** On a site about political quotes, a made-up fact or a fake source link is the worst outcome. Design it so every claim traces back to a page the agent actually read, and so it can never publish on its own.

## Where It Could Run

### Option A: Claude Code skill (terminal)

A skill in the repo (e.g. `.claude/skills/research-quote/`) holds the workflow instructions:

- what counts as a reliable source;
- tone, structure and length;
- how to handle claims that can't be verified.

You run the skill with a quote and its source. Claude searches and reads the web (WebSearch/WebFetch are built in), you refine the draft back and forth in chat, and it produces the final context, full details and sources.

- **Pros:**
  - No app code.
  - Interactive by default.
  - Runs on your Claude subscription.
  - The cheapest place to iterate on the instructions.
- **Con:** content lives in the **production** database, but this runs locally. The simplest bridge is pasting the output into the admin form. Later, an admin-only MCP tool on the site can close this gap (see [Option 3](#option-3-flip-it-the-site-becomes-an-mcp-connector-inside-their-claude-recommended)).

### Option B: In-app "Generate" button

An admin action queues a Laravel job that calls the Claude API.

- It uses `claude-opus-5` with Anthropic's server-side web search and web fetch tools. Those run on Anthropic's side, so there's no scraping code to write.
- It returns structured fields: context, full details and a list of sources.
- It saves them as a **draft suggestion**, never directly onto the quote. The admin reviews and accepts field by field.
- **Gaps to close:**
  - It needs the official PHP SDK (`anthropic-ai/sdk`), which is a new dependency.
  - It needs an API key stored as a Fly secret, and API use is billed per request.
  - **No queue worker runs on Fly yet.** `fly.toml` sets `QUEUE_CONNECTION = 'database'`, but nothing processes the queue, so jobs would never run. Nothing is queued today, so this isn't a current bug. A research run can take minutes, so it must be a background job, and that means adding a worker process.

### Option C: In-app chat panel

A chat panel on the quote edit page, next to the draft.

- Each message is sent to Claude along with the conversation history and the current draft.
- Claude edits the draft through a tool (e.g. `update_draft(field, text)`), so the draft pane updates live and you accept changes into the real fields.
- **The cost is a lot of work:** storing conversations, streaming or polling for replies, error handling and cost caps. It's essentially a small Claude Code built for one task.
- Anthropic's **Managed Agents** (beta) would host the conversation loop and state, but it includes a sandbox this task doesn't need.

### Comparison

| | Interactive | App code needed | Who pays | Works from anywhere |
|---|---|---|---|---|
| A: Claude Code skill | Yes | None | Your Claude subscription | No (your machine) |
| B: Generate button | No, one shot | Moderate | Your API key | Yes |
| B + "Refine" box | Partly | Moderate+ | Your API key | Yes |
| C: Chat panel | Yes | High | Your API key | Yes |

## Recommended Path

1. **Build the "full details" field and page first.** The agent needs somewhere to put its output.
2. **Start with Option A.** The hard part isn't the plumbing, it's the instructions and the quality bar. Run 10–20 quotes through it, note what you keep correcting, and feed that back into the instructions.
3. **Move it into the app only if you want it.** The skill's instructions become the API system prompt almost word for word.
   - Before building a full chat, try a **"Refine" box**: one instruction plus a regenerate button (e.g. "add more on the 2019 hearing"). It probably gives most of the chat's benefit for a fraction of the work.
   - Build Option C only if the Refine box turns out not to be enough.

## Guardrails (Design In From Day One)

- **Drafts only.** AI output always lands as a draft, and the quote's status never changes automatically. Consider an `ai_assisted` flag.
- **Real sources only.** Only accept source URLs that came back from an actual search or page fetch, never links the model wrote from memory. The existing `archived_url` field could be filled from the Wayback Machine.
- **Facts and interpretation stay separate.** The agent keeps what a source says apart from its own interpretation, and flags anything it couldn't verify.
- **Limited power.** Fetched web pages can contain instructions aimed at the model. Because the agent can only write a draft that gets reviewed, the damage is limited. That's another reason never to let it publish.
- **Access and cost limits.** Admin-only access, a limit on how often it can run, and a spending cap.
- **Cost.** Probably tens of cents to a couple of dollars per quote at Opus prices, depending on how many pages it reads. Measure a few real runs before relying on that estimate.

## Guest Users: Can They Bring Their Own Claude?

**Question:** once guest accounts (spec Phase 3) can submit quote requests, could they connect their own Claude to their account and use the agent chat for their submissions, so the site isn't paying for their usage?

### Option 1: "Connect your Claude.ai account" (Pro/Max subscription): not allowed

Anthropic's consumer terms (clarified February 2026, enforced from April 2026) say OAuth tokens from Free, Pro and Max plans may only be used with Claude.ai and Claude Code. Third-party products must use API keys from the Claude Console or a supported cloud provider. A button that bills a user's Claude subscription therefore isn't an option. **Don't build this.**

### Option 2: Bring your own API key: possible, but awkward

The user creates an API key in the Claude Console and pastes it into their account settings. That's a separate, pay-as-you-go account, not their Claude.ai subscription. The site stores the key encrypted (Laravel's `encrypted` cast) and uses it for that user's Option B/C requests, so the usage is billed to them.

- **Cons:**
  - Most people don't have a Console account or API credits.
  - The site holds secrets that can spend other people's money. That's a liability if there's a breach. Keys must never reach the browser or the logs, and users need a way to revoke them.
  - Support burden: invalid keys, exhausted credits.
  - Option C's chat UI still has to be built.

### Option 3: Flip it: the site becomes an MCP connector inside *their* Claude (recommended)

Instead of the site hosting a chat that uses their Claude, **their Claude talks to the site.** The site exposes an MCP server, and users add it as a custom connector in Claude.ai (or Claude Desktop or Claude Code). They research and chat inside Claude on their own plan, which is allowed because the usage stays inside Anthropic's app. When they're happy with the result, Claude calls the site's submit tool, which creates a pending submission on their account.

- **Pros:**
  - The site never holds their Claude credentials; their Claude holds a token for the site instead.
  - No chat UI to build, because Claude.ai is the chat UI.
  - The workflow instructions from Option A can be published as an **MCP prompt**, so their Claude follows the same rules you use.
  - `laravel/mcp` is already installed.
- **Possible tools:**
  - `search_quotes`: check for duplicates before submitting.
  - `research-quote` (prompt) or `get_submission_guidelines`: the workflow instructions.
  - `submit_quote_request(text, speaker, occurred_at, primary_source_url, context, full_details, sources[])`: creates a **pending** submission only. It runs the same validation rules as the web forms.
  - `list_my_submissions`: lets users check the status of their submissions.
- **Authentication:** Laravel MCP supports either Sanctum bearer tokens or OAuth 2.1 via Passport.
  - Adding a connector in Claude.ai goes through an OAuth sign-in, so that likely means **Laravel Passport**, a new dependency.
  - Sanctum tokens may be enough for Claude Code or Desktop set up with a header.
  - Confirm both when planning.
- **Cons:**
  - Users need a Claude plan that supports custom connectors (check current plan limits).
  - You don't control their model or prompt, so submissions are only as good as their session. Review matters even more.
  - It's a public API surface, so it needs rate limits and per-user quotas.

**Bonus for the admin workflow:** an admin-only tool on the same MCP server (e.g. `save_quote_draft`, gated by `isAdmin()`) would let Claude Code or Claude.ai write drafts straight into production. That closes Option A's "paste it in" gap without building any chat UI.

### Recommendation for Guests

Go with Option 3. Consider Option 2 only if the chat specifically has to live inside the site.

Either way, every guest submission lands as **pending** in the approval workflow. AI-assisted submissions read as polished and confident, which makes them easy to wave through, so reviewers should check the sources, not the prose.

## Prerequisites & Dependencies

- The "full details" field and a public detail page per quote.
- A queue worker process on Fly (needed for Options B and C).
- New dependencies, which need approval first:
  - `anthropic-ai/sdk` (Options B and C);
  - `laravel/passport` (Option 3 OAuth, if Sanctum isn't enough).
- `ANTHROPIC_API_KEY` stored as a Fly secret (Options B and C).
- For guests (Phase 3):
  - registration re-enabled;
  - a submission and approval workflow;
  - Gates/Policies.

## Verify Before Starting

As of September 2026:

- **Model:** `claude-opus-5` (default), with adaptive thinking (`thinking: {type: "adaptive"}`).
- **Server tools:** `web_search_20260209` and `web_fetch_20260209`, both run by Anthropic. Web fetch can only fetch URLs that already appeared in the conversation, which is useful: it can't invent a URL and fetch it.
- **PHP SDK:**
  - install with `composer require anthropic-ai/sdk`;
  - the beta tool runner is `$client->beta->messages->toolRunner()`;
  - streaming is `$client->messages->createStream(...)`;
  - top-level named arguments are camelCase (`maxTokens`).
- **Managed Agents:** still in beta. Only worth it if building Option C.
- **`laravel/mcp`:** the docs describe the latest version. Check that the installed version (v0 at the time of writing) supports the OAuth routes and prompts needed.
- **Anthropic policy:** check its current rules on using consumer subscriptions in third-party apps.
- **Cost:** measure real runs rather than trusting the estimate above.

## Open Questions

- Which fields should the agent draft: only context, full details and sources, or `claim` and `reality_check` too?
- Should drafts go in a separate table (e.g. `quote_drafts`) or in draft columns on quotes?
- Should source reliability rules be an allow-list, a block-list or guidance only?
- Should AI-assisted content be labelled publicly?
- Should guest submissions use a separate `quote_submissions` table, or be quotes with `pending` status plus `submitted_by`?

## Sources

- [Anthropic bans Claude subscription OAuth in third-party apps (WinBuzzer, 2026-02-19)](https://winbuzzer.com/2026/02/19/anthropic-bans-claude-subscription-oauth-in-third-party-apps-xcxwbn/)
- [Anthropic officially bans third-party subscription authentication (GIGAZINE)](https://gigazine.net/gsc_news/en/20260220-anthropic-third-party-block/)
- [Anthropic cuts off Claude subscriptions for third-party AI agents (VentureBeat)](https://venturebeat.com/technology/anthropic-cuts-off-the-ability-to-use-claude-subscriptions-with-openclaw-and)
- [Laravel MCP documentation: authentication (Sanctum / Passport OAuth 2.1)](https://github.com/laravel/docs/blob/13.x/mcp.md)
