# WBD Real Estate Suite — Product & Technical Plan

**Product:** WBD Real Estate Suite · **Company:** WPBrand Digital (wpbranddigital.org) · **Version covered:** 1.0.0 (built) and the roadmap after it.
**Business model decision:** every feature in this plan ships free on WordPress.org. Revenue comes from optional services and future add-ons that connect to outside systems (see §11).

---

## 1. Product strategy

**Vision.** The default real estate toolkit for WordPress block themes: fast, native and free.

**Mission.** Give independent agents and small agencies a professional listings site with lead management in under an hour, without page builders, API keys or subscriptions.

**Positioning.** A lightweight, Gutenberg-native suite for listings, property search and leads, built for small real estate businesses. Competing plugins are either heavy theme-bound suites or freemium plugins that lock search, maps and CRM behind a paywall.

**Value proposition.**
- Professional listing pages out of the box (automatic layouts, block-theme templates, 10 patterns).
- A real lead pipeline (stages, assignment, notes, timeline, CSV) and not just an email form.
- Works with any currency, area unit and language, with no external services required.
- Performance and compliance by default (no jQuery, around 5 KB of interactive JS, GDPR tools, schema markup).

**Ideal customer profiles.**

| ICP | Size | Main job | Why they choose WBD |
|---|---|---|---|
| Independent agent | 1 person, 5–60 listings | Show listings and capture inquiries | Free, fast setup, looks premium |
| Small agency | 2–15 agents | Share leads across agents and measure them | Agent roles, assignment, "own leads only" |
| Property consultant / developer | 1–3 projects | Landing pages per project | Patterns and blocks inside any page |
| WordPress freelancer / agency | Many clients | Repeatable real estate builds | Block-native, theme-agnostic, overridable |

**Market segments.** English-speaking markets first (US, UK, AU, CA, IE, ZA, UAE, IN), then Spanish, Portuguese, French and Arabic through translate.wordpress.org. The flexible currency, area units (including katha, marla, tsubo and ping) and RTL support cover South Asia, the Middle East and East Asia.

**Customer problems → solutions.**

| Problem | Solution in v1.0 |
|---|---|
| Real estate themes lock content to the theme | Content lives in a plugin with CPTs and works with any theme |
| Heavy builders hurt Core Web Vitals | Dynamic blocks, assets loaded only when used, lazy images |
| Leads get lost in email | Built-in CRM with stages, assignment, notes and CSV |
| Maps need paid API keys | OpenStreetMap embed behind a consent click |
| Free versions are crippled | Everything free, including search, maps, CRM and import |
| Spam inquiries | Honeypot, time trap, per-IP rate limit and link limits |

**Competitor comparison** (based on public feature pages; check again before publishing any comparison page).

| Capability | WBD (free) | Typical freemium listing plugin | Theme-bundled suite |
|---|---|---|---|
| Block-native editing | ✓ | Partial (shortcodes or widgets) | Rare |
| Advanced search and filters | ✓ | Often paid | ✓ (theme-locked) |
| Maps without API key | ✓ OSM | Usually Google key | Google key |
| Lead CRM | ✓ | Rare / paid | Basic |
| Favorites & compare | ✓ | Paid | ✓ |
| CSV import with undo | ✓ | Paid | Varies |
| Frontend submission | ✓ | Paid | ✓ |
| jQuery-free | ✓ | Rare | Rare |

**Scope and non-goals (v1).** In scope: listings, search, agents, inquiries, CRM, notifications, import/export, analytics, SEO, privacy. Non-goals: MLS/IDX feeds, payments or paid listings, booking calendars with availability, multi-language content management (left to translation plugins), membership packages, and a native mobile app.

**Assumptions.** Small businesses list fewer than 2,000 properties. Most sites run block themes or modern classic themes. A host's `wp_mail` or an SMTP plugin handles email.

**Risks.** See the risk register in §20 N.

**Smallest useful MVP.** CPTs and meta, editor sidebar, listing grid, single layout, search bar, inquiry form, a leads list with statuses and email notification. v1.0 ships far beyond this, as listed below.

**Long-term vision.** A connected local real estate stack: MLS/RESO import add-ons, an AI listing-description helper (opt-in, bring your own key), open-house scheduling, a client portal and saved searches with alerts.

---

## 2. Feature inventory (all Free)

Priority: P0 = launch blocker, P1 = launch, P2 = later. Release: MVP / 1.0 / Future.

| Area | Feature | User | Workflow | Depends on | P | Release |
|---|---|---|---|---|---|---|
| Property mgmt | `wbd_property` CPT with 20 typed meta fields | Agent/Admin | Block editor plus sidebar panels | Meta API | P0 | MVP |
| Property mgmt | Types, statuses, hierarchical locations, features | Admin | Term screens under Real Estate | Taxonomies | P0 | MVP |
| Property mgmt | Gallery manager (reorder/remove), video, tour URL | Agent | Sidebar "Gallery, video & tour" | Media Library | P0 | MVP |
| Property mgmt | Featured flag, star toggle, sortable admin columns | Admin | List table | — | P1 | 1.0 |
| Catalog | Property Listings block (grid/list, 2 card styles) | Visitor | Page or archive | Query builder | P0 | MVP |
| Catalog | Archive and taxonomy templates (block and classic) | Visitor | `/properties/` | Templates | P0 | MVP |
| Search | Search bar, sidebar filters, compact hero, Buy/Rent tabs | Visitor | GET form → results | Query | P0 | MVP |
| Search | Keyword across title, content, address and location | Visitor | `?keyword=` | `posts_search` filter | P1 | 1.0 |
| Search | Sort, grid/list toggle, filter chips, pagination | Visitor | Toolbar | — | P1 | 1.0 |
| Details | Automatic single layout, Property Details block | Visitor | Single page | View | P0 | MVP |
| Details | Property Field block (9 variations) | Designer | Custom templates | Context | P1 | 1.0 |
| Details | Accessible lightbox, "View all photos" | Visitor | Dialog | Interactivity API | P1 | 1.0 |
| Agents | `wbd_agent` CPT, profile, directory, agent role | Admin | Agents screen | Roles | P0 | MVP |
| Agents | Link an agent to a WP user for "own leads" | Admin | Sidebar | Caps | P1 | 1.0 |
| Inquiries | Inquiry, viewing and contact forms (REST plus no-JS) | Visitor | Form → lead | LeadService | P0 | MVP |
| CRM | Leads list, stages, bulk actions, search, filters | Agent/Admin | Leads screen | Custom tables | P0 | MVP |
| CRM | Lead detail, notes, timeline, assignment, manual add | Agent | Lead screen | Notes table | P1 | 1.0 |
| Submission | Frontend submission plus "My properties" | Owner/User | Block → pending → approve/reject | Media | P1 | 1.0 |
| Favorites/compare | Heart, compare, recently viewed (localStorage) | Visitor | Saved Properties block | REST `/properties` | P1 | 1.0 |
| Maps | OSM embed with consent | Visitor | Click "Load map" | — | P1 | 1.0 |
| Mortgage | Calculator plus "Est. $x/mo" in header | Visitor | Inputs | — | P1 | 1.0 |
| Import/export | CSV import (dry run, dedupe, undo), exports | Admin | Tools | — | P1 | 1.0 |
| Email | 7 editable templates, test email, timeline logging | Admin | Settings → Emails | wp_mail | P0 | MVP |
| SEO | RealEstateListing JSON-LD, noindex for filters | Search engines | Automatic | — | P1 | 1.0 |
| Analytics | Views counter, KPIs, funnel, donut, charts | Admin | Overview and Analytics | — | P1 | 1.0 |
| Settings | Currency, units, slugs, accent colour, toggles | Admin | Settings tabs | Options API | P0 | MVP |
| Privacy | Consent, export/erase, retention cron | Admin | Settings, Tools → Privacy | Core privacy | P0 | MVP |
| Future | Saved searches with email alerts | Visitor | — | Cron, accounts | P2 | Future |
| Future | Multi-marker map view of results | Visitor | — | Leaflet (bundled) | P2 | 1.1 |
| Future | Agent self-registration with approval | Agent | — | Users | P2 | 1.1 |
| Future | RESO Web API / MLS import add-on | Agency | — | External APIs | P2 | Future |

---

## 3. Gutenberg block library (14 blocks plus variations)

**Architecture rules.** All blocks are dynamic (PHP `render.php`) because they depend on live data, settings and the current post. Editor previews use `ServerSideRender`, so the editor matches the frontend exactly. Small "atomic" blocks are folded into a parent block with variations rather than shipped as many tiny blocks. Shared state (favorites, compare, lightbox, forms, mortgage) runs on one Interactivity API store, `wbdres`, shipped as a single script module.

| Block (slug) | Purpose | Key settings | Data / query | Variations |
|---|---|---|---|---|
| Property Listings `wbdres/property-listings` | Grid or list of properties | Layout, card style, columns 1–4 (≤2 tablet, 1 phone), ratio, heading level, per page, order, types/statuses/locations, featured, agent (any/current/pick), similar, exclude current, offset, 9 card toggles, toolbar, sort, view toggle, chips, pagination, empty text | WP_Query plus URL filters (`inheritSearch`) or main query | Featured, Latest, Search Results, Similar, Agent Properties |
| Property Search `wbdres/property-search` | Search form | Layout bar/sidebar/compact, heading, button text, results page, 9 field toggles | GET to archive or results page | Sidebar Filters, Property Type Filter |
| Property Details `wbdres/property-details` | Full single layout | Property picker, 14 section toggles | Context `postId` | — (single instance) |
| Property Field `wbdres/property-field` | One data point | Field, heading level, labels, property | Context | Title, Price, Address, Status Badge, Specifications, Key Facts, Features, Type, Reference |
| Property Gallery `wbdres/property-gallery` | Photo grid and viewer | Hero/grid/stack, viewer on/off, max photos | Featured image plus `_wbdres_gallery` | — |
| Property Map `wbdres/property-map` | OSM map | Height, zoom | lat/lng | — |
| Property Video `wbdres/property-video` | oEmbed and tour | Property | URLs | — |
| Agent Profile `wbdres/agent-card` | Agent card | Agent (auto from page or property), layout, bio, contact, social, count | Agent CPT | — |
| Agent Directory `wbdres/agent-directory` | Agent grid | Count, columns, order, toggles | Agent CPT | — |
| Property Inquiry Form `wbdres/inquiry-form` | Lead capture | Type, heading, button, phone shown/required, property, agent | REST `/inquiries` | Schedule a Viewing, Contact Agent |
| Mortgage Calculator `wbdres/mortgage-calculator` | Payment estimate | Price (auto), rate, term, down payment | Settings defaults | — |
| Saved Properties `wbdres/saved-properties` | Favorites, recent, compare | Mode, heading, columns | localStorage → REST `/properties?include=` | Recently Viewed, Property Comparison |
| Frontend Property Submission `wbdres/property-submission` | Submit and "My properties" | Heading, dashboard toggle | admin-post plus media | — |
| Browse by Type or Location `wbdres/property-terms` | Term cards or chips | Taxonomy, style, count, hide empty, top level | get_terms | Property Location Filter |

**Accessibility for all blocks.** Real `<label>`s, fieldsets with legends for groups, `aria-live` status messages, focus-visible rings, 44 px targets, a native `<dialog>` lightbox with Esc and arrow keys, a screen-reader label on every icon button, and reduced-motion support.

**Requested items covered another way.** Featured, Latest, Agent Properties and Similar are Listings variations. Title, Price, Address, Specifications, Features, Description and Status Badge are Property Field variations (the description is the post content). Property Statistics, CTA and Login/Register are covered by patterns plus the core Login/out block.

---

## 4. Forms and workflows

| Form | Fields (✱ = required) | Validation | Storage | Notifications | Spam/limits |
|---|---|---|---|---|---|
| Property inquiry | Name✱ (2–100), Email✱, Phone, Message (≤3000, pre-filled), Consent✱ | Server-side; native browser hints | `wbdres_leads` type `inquiry` | Admin, agent, auto-reply | Honeypot `website`, 2 s time trap, 5/hour per hashed IP (configurable), >3 links rejected |
| Schedule a viewing | Name✱, Email✱, Phone✱, Preferred date✱ (future), Time (morning/afternoon/evening), Message, Consent✱ | Date format and future check | type `viewing` plus date/time | Same | Same |
| Contact agent | Name✱, Email✱, Phone, Message✱ (≥5), Consent✱ | — | type `contact`, `agent_id` | Same | Same |
| Advanced search | keyword, status, location[], type[], min/max price, beds, baths, min/max area, feature[] | Sanitised whitelist (`Query::filters_from`) | URL only | — | `noindex,follow` |
| Frontend submission | Title✱ (≥5), description, type, status, price, suffix, beds, baths, area, year, address, city/area, postal code, country, features[], photos (≤N, JPEG/PNG/WebP ≤5 MB), video URL | Nonce, capability, real MIME check, existing terms only | Pending `wbd_property` | Admin "awaiting review"; submitter approved/rejected | 10 per user per day |
| Lead follow-up (admin) | Stage, agent, note | Nonce and scope | Notes table | Assignment email | — |
| Manual lead (admin) | Name✱, Email✱, phone, property, agent, stage, notes | — | type `manual` | — | Capability |

**States.** Success is shown inline (`role=status`) and the form is reset. Errors are inline (`role=alert`) and list every problem. Without JavaScript the form redirects with `?wbdres_sent=ok|error`.
**Consent wording.** Editable; the privacy policy link is added automatically.
**Mobile.** Single-column fields, 16 px inputs (no iOS zoom), and a sticky "Contact agent" bar on property pages.
**Review workflow.** Submissions are created as Pending → Publish sends the "approved" email; the Reject row action asks for a reason → Draft plus "needs changes" email.
**Not built.** Agent registration (admins create agent users; planned for 1.1). Property reviews: left out on purpose, because reviews of private homes add moderation and legal risk for little value.

---

## 5. Admin dashboard

Menu **Real Estate**: Overview · All Properties · Add New Property · Property Types · Property Statuses · Locations · Features & Amenities · Agents · Leads (count bubble for new leads) · Analytics · Tools · Settings.

| Screen | Purpose | Components | Permissions |
|---|---|---|---|
| Overview | Daily cockpit | Welcome header, Add property / Add lead buttons, 8 tinted KPI cards, lead funnel, leads-by-type donut, upcoming viewings, recent inquiries, most viewed, 3-step setup card when empty | Agents see their own lead figures |
| All Properties | Manage listings | Thumbnail, price, details, agent, ★ toggle, views; filters by type, status and agent; sortable by price and views; Reject on pending submissions | `edit_wbd_properties` |
| Leads | CRM list | Status views with counts, type and agent filters, search, sortable columns, bulk "Mark as…", Export CSV, Delete (confirm) | Managers: all; agents: own |
| Lead detail | Work a lead | Contact card, message, consent, source page, activity timeline; sidebar for stage, agent and note; reply by email; call; delete (confirm) | Scope check |
| Analytics | Reporting | 7/30/90/365-day range, KPIs with trend, column chart plus data table, pipeline, sources, top properties by leads and views | `wbdres_manage_leads` |
| Tools | Data ops | CSV import (dry run, dedupe, status, images), recent imports with Undo, exports, sample content, demo pages, system status | `manage_wbdres` |
| Settings | Configuration | Tabs: General, Display, Forms & Leads, Frontend Submission, Email Templates, Advanced | `manage_wbdres` |

**KPI definitions.**

| KPI | Calculation |
|---|---|
| New leads | Count of leads with status `new` (in scope) · trend = leads in the last 30 days vs the previous 30 |
| Follow-ups | Status `contacted` plus `qualified` |
| Viewings | Status `viewing` |
| Deals won | Status `won` · conversion = won ÷ all leads |
| Published properties | `wp_count_posts()->publish` · featured = published with `_wbdres_featured=1` |
| Pending review | `wp_count_posts()->pending` |
| Active agents | Published `wbd_agent` |
| Property views | SUM(`_wbdres_views`): one per visitor per property per 6 h, bots excluded |

Stats are cached for 10 minutes in a transient and flushed whenever a property is saved.
**Responsive admin.** KPI and panel grids collapse 4→2→1 and 3→2→1 columns.

---

## 6. Frontend templates and design system

| Template | How it is provided | Layout |
|---|---|---|
| Property archive and taxonomy archives | Block template `archive-wbd_property` / `taxonomy-*` (WP 6.7+, editable); classic: `templates/archive-property.php` (theme override `yourtheme/wbd-real-estate-suite/archive-property.php`) | Title → search bar → toolbar → 4-column grid → pagination |
| Single property | Block template `single-wbd_property` (Property Details block); classic: automatic layout via `the_content` (turns off when the editor places plugin blocks) | Hero photo grid → [status · price · title · address · est./mo | key facts · save · compare · share] → What's special → About → Facts & features → Video → Location → Payment calculator ‖ sticky agent card plus inquiry plus viewing → Recommended → CTA band → mobile contact bar |
| Search results | Any page with Listings (URL filters) | Sidebar filters 300 px plus results |
| Agent directory | Pattern | Centered header plus 3-column cards |
| Single agent | Block template `single-wbd_agent`; classic via content | Agent card plus contact form → About → Listings |
| Submission / user dashboard | Pattern plus block | Login box for visitors; "My properties" table plus form |
| Favorites, compare | Patterns | Client-rendered with skeleton loading and empty states |
| Inquiry success | Inline success message (no extra page needed) | — |
| No results | Empty state with "Clear all filters" | — |

**Patterns (10).** Real Estate Homepage, Property Search and Listing Page, Single Property Landing Page, Agent Directory, Agent Profile Landing Page, Frontend Submission Page, Favorite Properties, Property Comparison, Contact & Inquiry, Property CTA Banner. All 10 were validated with `wp.blocks.parse()` (no invalid blocks).

---

## 7. Property card and single-property UX

- **Card (modern).** 4:3 photo (configurable), status and featured badges top-left, accent price with a muted suffix, round heart and compare buttons, title (the whole card is clickable through a stretched link), address with pin, a "4 Beds · 3 Baths · 2,450 sq ft" icon row, optional type and agent footer.
- **Card (overlay).** Price pill on the photo, heart and compare top-right, labelled Bedrooms / Bathrooms / Area columns.
- **Card (list).** Horizontal photo (38 %) plus excerpt.
- **Price.** Locale separators, symbol position, "Price on request", suffix such as "/month".
- **Missing data.** No photo → illustrated placeholder; missing specs are left out; no coordinates → map hidden; rentals → no mortgage or price per area.
- **Loading.** Skeleton shimmer for client-rendered lists; lazy images with `fetchpriority=high` on the first image.
- **Mobile.** Sticky bottom bar with price and "Contact agent"; the gallery collapses to 1 large plus 2 photos.

---

## 8. Database and data architecture

| Data | Storage | Why |
|---|---|---|
| Properties | CPT `wbd_property` (capability type `wbd_property/wbd_properties`, `map_meta_cap`) | Revisions, REST, search, sitemaps and SEO plugins work for free |
| Agents | CPT `wbd_agent` | Public profiles with their own URLs |
| Type / status / location / features | Hierarchical taxonomies `wbd_property_type`, `wbd_property_status`, `wbd_property_location`, `wbd_property_feature` | Fast joins, archive URLs, counts |
| Specifications | Post meta `_wbdres_*` (protected, REST-exposed with `auth_callback`) | Native editing and REST |
| Leads and activity | Custom tables `{prefix}wbdres_leads`, `{prefix}wbdres_lead_notes` | Private, high-volume, filtered by status/date; must never appear in post queries, search or sitemaps |
| Settings | Single option `wbdres_settings` (autoload off) plus `wbdres_email_templates` | One row, typed sanitizer |
| Caches | Transients (dashboard stats, rate limits, view dedupe), object cache group `wbdres` | Works with persistent object caches |
| Favorites / compare / recent | Visitor localStorage | No personal data stored on the server |

**Property meta.** `_wbdres_price` number · `_wbdres_price_suffix` string(100) · `_wbdres_price_on_request` bool · `_wbdres_bedrooms` int · `_wbdres_bathrooms` number (rounded to .5) · `_wbdres_area` / `_wbdres_land_area` number ≥0 · `_wbdres_garages` int · `_wbdres_year_built` int 1000–2200 · `_wbdres_reference` string · `_wbdres_address` · `_wbdres_postal_code` · `_wbdres_country` · `_wbdres_lat` (−90…90) · `_wbdres_lng` (−180…180) · `_wbdres_hide_map` bool · `_wbdres_gallery` int[] (≤60) · `_wbdres_video_url` / `_wbdres_tour_url` URL · `_wbdres_featured` bool · `_wbdres_agent_id` int · `_wbdres_views` int (read-only) · internal: `_wbdres_submitted`, `_wbdres_import_batch`, `_wbdres_reject_reason`, `_wbdres_sample`.

**Agent meta.** title, email (removed from public REST output), phone, WhatsApp, license, agency, languages, website, LinkedIn, Facebook, Instagram, X, `_wbdres_agent_user_id`.

```sql
CREATE TABLE wp_wbdres_leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  property_id bigint(20) unsigned NOT NULL DEFAULT 0,
  agent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  type varchar(20) NOT NULL DEFAULT 'inquiry',      -- inquiry|viewing|contact|manual
  status varchar(20) NOT NULL DEFAULT 'new',        -- new|contacted|qualified|viewing|negotiation|won|lost
  name varchar(191) NOT NULL DEFAULT '',
  email varchar(191) NOT NULL DEFAULT '',
  phone varchar(50) NOT NULL DEFAULT '',
  message text NULL,
  preferred_date date NULL DEFAULT NULL,
  preferred_time varchar(20) NOT NULL DEFAULT '',
  source_url varchar(500) NOT NULL DEFAULT '',
  ip_hash char(64) NOT NULL DEFAULT '',             -- HMAC of IP, never the raw IP
  consent tinyint(1) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL, updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY status_created (status,created_at), KEY property_id (property_id),
  KEY agent_id (agent_id), KEY email (email), KEY created_at (created_at)
);
CREATE TABLE wp_wbdres_lead_notes (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
  author_id bigint(20) unsigned NOT NULL DEFAULT 0,
  type varchar(20) NOT NULL DEFAULT 'note',         -- note|status|assign|email|system
  note text NULL, created_at datetime NOT NULL,
  PRIMARY KEY (id), KEY lead_id (lead_id)
);
```

**Relationships.** property → agent (`_wbdres_agent_id`); agent → WP user (`_wbdres_agent_user_id`); lead → property, agent and user; note → lead.
**Migrations.** `wbdres_db_version` is compared on `plugins_loaded`; `dbDelta` is idempotent (two spaces after `PRIMARY KEY`, one field per line). Future migrations are added as version-gated steps in `Installer::maybe_upgrade()`. Multisite: per-site lazy install.
**Uninstall.** Data is kept by default. With "Delete all data on uninstall" on, posts, terms, tables, options, transients, roles and capabilities are removed on every site.
**Privacy.** Core exporter and eraser by email, policy text, retention cron (`wbdres_daily_cleanup`, 500 rows per run).
**Performance.** Numeric sort keys are guaranteed to exist (so meta sorts never drop posts), `DECIMAL` casts for ranges, `no_found_rows` when there is no pagination, agent counts cached, keyword search adds two indexed EXISTS subqueries. For more than 5,000 listings, the plan is a denormalised price/area lookup table (P2).

---

## 9. Technical architecture

```
wbd-real-estate-suite/
├─ wbd-real-estate-suite.php     Bootstrap: constants, PSR-4 autoloader, activation hooks
├─ uninstall.php                 Opt-in data removal (multisite aware)
├─ readme.txt                    WordPress.org readme
├─ includes/                     PHP (namespace Wbdres\RealEstate)
│  ├─ Plugin.php                 Singleton orchestrator
│  ├─ Settings.php               Typed option access + sanitizer
│  ├─ Install/                   Installer (dbDelta, migrations, cron), Roles
│  ├─ PostTypes/Registrar.php    CPTs, taxonomies, default terms, meta defaults
│  ├─ Meta/                      PropertyMeta, AgentMeta (REST, sanitize, auth)
│  ├─ Support/                   Property read model, Format (price/area/i18n)
│  ├─ Leads/                     LeadRepository (prepared SQL), LeadService (validation, spam, scope)
│  ├─ Rest/Routes.php            wbdres/v1: inquiries, properties, view, leads
│  ├─ Frontend/                  Assets, View (escaped markup), Listings, Search, Query, Templates, Forms
│  ├─ Blocks/Blocks.php          Registers build/blocks/*, helpers
│  ├─ Patterns/Patterns.php      Registers patterns/*.php
│  ├─ Email/                     Templates, Mailer, Notifications
│  ├─ Schema/StructuredData.php  JSON-LD
│  ├─ Privacy/                   Exporter/eraser, retention
│  └─ Admin/                     Menu, Dashboard, Leads (+ListTable), Analytics, Tools, SampleData, Settings, Columns, EditorAssets, Notices
├─ src/                          JS/SCSS source (@wordpress/scripts)
│  ├─ blocks/<name>/             block.json, index.js (edit), render.php
│  ├─ shared/editor.js           Preview, TermTokens, PostPicker, Toggles
│  ├─ editor/sidebar.js          PluginDocumentSettingPanel panels
│  ├─ frontend/view.js           Interactivity API store (script module)
│  ├─ frontend/style.scss        Design system
│  └─ admin/                     admin.js, admin.scss
├─ build/                        Compiled assets (shipped)
├─ patterns/                     10 pattern files
├─ templates/archive-property.php  Classic fallback (overridable)
├─ webpack.config.js, package.json, phpcs.xml.dist
└─ docs/PLAN.md
```

**When to use what.** Dynamic blocks for anything data-driven (all 14). Static blocks: none needed. InnerBlocks: avoided, because layouts are composed with core blocks in patterns instead. Variations replace look-alike blocks. Patterns provide page starters. The Interactivity API covers client state (favorites, lightbox, forms, mortgage, saved lists) with progressive enhancement: everything important works without JS.

**Build.** `npm install --legacy-peer-deps && npm run build` (webpack 5, Babel and SCSS through @wordpress/scripts 31, WordPress packages externalised, zero warnings). `ajv@^8` is pinned so builds with `--legacy-peer-deps` don't break.

---

## 10. Free vs Pro — critical review

The original list put advanced search, maps, mortgage, favorites, CSV and analytics behind Pro. For a new plugin with no reviews, that risks the "crippled free version" reputation that drags down ratings and slows WordPress.org growth. **Decision: everything in the product is free.** The paid offer is limited to things that cost WPBrand money, require outside services, or save agencies time at scale:

| Free (this plugin) | Future paid add-ons / services (hypotheses) |
|---|---|
| All 14 blocks, templates, patterns | MLS / RESO Web API / IDX feed importer |
| Search, filters, maps, mortgage | Premium pattern and starter-site packs (design work) |
| Full CRM, emails, analytics | Saved-search email alerts at scale / SMS / WhatsApp API notifications |
| Submission, favorites, compare | CRM connectors (HubSpot, Pipedrive, Zapier) |
| CSV import/export | White-label and multi-site agency license with priority support |
| — | Done-for-you setup and migration service |

---

## 11. Pricing and monetization (hypotheses to test, not validated)

| Plan | Price (annual) | Includes |
|---|---|---|
| Free | $0 | Everything in this plugin, forum support |
| Add-on: Single Site | $49/yr | 1 site, any one connector add-on, email support |
| Agency | $149/yr | 25 sites, all add-ons, priority support |
| Developer | $299/yr | Unlimited sites, white-label, early access |
| Setup service | from $199 one-off | Install, import, design tweaks |

Renewals at 30 % off. Upgrades are prorated. Lifetime deals only as a capped 100-seat launch offer ($399 Agency) to fund development. Beta offer: the first 50 agencies get the Agency plan free for 12 months in exchange for feedback and an honest review (no review incentive tied to rating).

---

## 12. SEO, performance, security, accessibility (implemented)

- **SEO.** Semantic article/header/section/aside markup; JSON-LD `RealEstateListing` + `Offer` (price, currency, sale/lease) + property `about` (bedrooms, bathrooms, floor size with unit code, address, geo, amenities) + `RealEstateAgent`; `noindex,follow` on filtered or sorted URLs; clean archive and taxonomy URLs; alt text falls back to the property title; paginated archives through core.
- **Performance.** CSS ~36 KB minified loaded only with plugin output; view module ~5 KB; responsive `sizes`; lazy images; no jQuery; cached aggregates; no external fonts.
- **Security.** Capabilities on every screen and action; nonces on all admin and logged-in actions; REST `permission_callback` everywhere (public endpoints are rate-limited); `$wpdb->prepare` for all custom SQL; sanitize on input and escape on output; real MIME checks on uploads; CSV formula-injection guard; agent emails hidden from public REST; IPs stored only as an HMAC.
- **Privacy.** Consent checkbox, retention, export/erase, policy text, map consent, browser-only favorites.
- **Accessibility (WCAG 2.2 AA target).** Labels, fieldsets, live regions, keyboard-operable dialog, focus rings, 44 px targets, colour contrast, reduced motion, RTL stylesheets.
- **i18n.** All strings in the `wbd-real-estate-suite` text domain, JS translations through `wp_set_script_translations`, locale-aware number formatting in the calculator.

---

## 13. Email system

| Event | Recipients | Key variables |
|---|---|---|
| New lead (admin) | Notification emails or admin email; Reply-To = lead | `{lead_name} {lead_email} {lead_phone} {lead_message} {lead_type} {property_title} {property_url} {preferred_date} {preferred_time} {lead_admin_url}` |
| New lead (agent) | Agent email or linked user's email | + `{agent_name}` |
| Auto-reply | The visitor | `{lead_name} {property_title}` |
| Lead assigned | New agent | `{lead_admin_url}` |
| Property submitted | Admin | `{submitter_name} {property_edit_url}` |
| Approved / needs changes | Submitter | `{property_url} {reason}` |

Each template can be switched on or off and its subject and body edited. There is a test email button. Results ("Admin notification: sent") are logged to the lead timeline without storing email content. Emails use an HTML wrapper with escaped text and auto-linked URLs.

---

## 14. Import, export and integrations

CSV columns: `title, description, excerpt, post_status, listing_status, type, location, features, price, price_suffix, price_on_request, bedrooms, bathrooms, area, land_area, garages, year_built, reference, address, postal_code, country, lat, lng, video_url, tour_url, featured, agent, images` (aliases accepted, `|` separates multiple values).
Import flow: dry run → report (mapped/ignored columns, preview, row errors) → real import (skip or update duplicates by `reference`, draft or publish, optional image download) → batch recorded → **Undo import**. Limit 1,000 rows / 10 MB per file.
Integrations: works with any SMTP plugin, SEO plugin (Yoast/Rank Math sitemaps include the CPTs) and translation plugin (WPML/Polylang see standard CPTs and taxonomies). Future: Leaflet multi-marker map, RESO Web API, Zapier webhooks — all optional, none required.

---

## 15. Design system

Tokens: `--wbdres-accent` (setting, default #0f766e), accent-soft (10 % mix), text #0f172a, muted #64748b, border #e6e8ec, soft surface #f5f6f8, radius 16/10/pill, two shadows, focus ring 3 px at 35 % accent. Fonts are inherited from the theme. Breakpoints: 600 / 782 / 1024 px. Components: pill buttons (solid, outline, light, block, small), round icon actions, badges, chips (static, removable, checkbox), cards, segmented control, dropdown panels, filter accordions, tables, dialog, notices, skeletons, empty states. Hardened against aggressive theme button styles. The admin uses its own indigo palette, tinted KPI cards, a funnel, a CSS conic donut and bar charts with data-table fallbacks.

---

## 16. Roadmap (solo developer)

| Phase | Deliverables | Acceptance | Effort | Status |
|---|---|---|---|---|
| 0 Research | Interviews with 10 agents, competitor audit | 3 validated pains | 1 wk | Before marketing |
| 1 Foundation | Bootstrap, autoloader, installer, roles, settings | Activates cleanly on WP 6.8–7.1 / PHP 7.4–8.4 | 1 wk | ✅ |
| 2 Property mgmt | CPTs, meta, sidebar, columns | Full listing edited in the editor | 1.5 wk | ✅ |
| 3 Core blocks | 14 blocks, variations, SSR previews | Editor matches frontend | 3 wk | ✅ |
| 4 Leads | Forms, REST, CRM, emails | Lead lifecycle end-to-end | 2 wk | ✅ |
| 5 Templates & patterns | Block and classic templates, 10 patterns | No invalid blocks; TT5 and TT1 pass | 1.5 wk | ✅ |
| 6 Testing & security | PHPCS (WPCS 3) clean, E2E flows, permissions | 0 sniffs; flows pass | 1 wk | ✅ |
| 7 WordPress.org launch | Readme, assets, SVN, support docs | Approved by the plugins team | 2–4 wk review | Next |
| 8 Paid add-ons | Connector add-on #1, store, licensing | 10 paying customers | 4 wk | After 500 installs |

---

## 17. Testing and QA

**Automated, done in this build:** PHPCS with WordPress Coding Standards 3 (0 errors, 0 warnings); `php -l` on every file; webpack build with 0 warnings; Playwright E2E on WordPress 7.1.5:

- favorites and compare persistence
- URL filters, keyword over address and location, sort, list view, `noindex` on filtered URLs
- lightbox open, arrow keys and Esc
- mortgage recalculation
- inquiry and viewing submit through REST, no-JS fallback
- spam checks: honeypot 400, validation 422, rate limit
- anonymous `/leads` returns 401; agent email not leaked through REST
- saved and compare pages
- admin screens with no PHP notices
- lead stage and note, bulk actions, settings save
- CSV dry run, import, undo, export
- all 10 patterns valid; editor sidebar panels present
- agent role scoping
- mobile 390 px
- classic theme (Twenty Twenty-One) and block theme (Twenty Twenty-Five)

**Before each release:**

| Area | Test |
|---|---|
| Matrix | WP 6.8, 6.9, 7.0, 7.1, 7.2-RC × PHP 7.4, 8.1, 8.3, 8.4 |
| Themes | Twenty Twenty-Five / Four / Twenty-One, Astra, Kadence, GeneratePress, Blocksy, OllieWP |
| Devices | iPhone SE/15, Pixel, iPad, 1366/1440/1920 desktop |
| Accessibility | axe DevTools, keyboard-only pass, NVDA/VoiceOver on forms and dialog |
| Data | 5,000 generated properties: archive TTFB < 400 ms with object cache |
| Security | Plugin Check (PCP), WPScan, manual nonce/capability review of new endpoints |
| Migrations | Upgrade from previous tag with data; multisite network activation |

---

## 18. WordPress.org launch plan

- **Name/slug:** "WBD Real Estate Suite" / `wbd-real-estate-suite`.
- **Short description (131 chars):** "Gutenberg-native property listings, search, agent profiles, inquiry forms and a built-in lead CRM. Lightweight, fast and 100% free."
- **Tags:** real estate, property listings, realtor, property management, real estate agent.
- **Long description:** benefits → feature groups → built-right list → external services.
- **Screenshots (10):** homepage, archive, sidebar search, single, mobile, compare, dashboard, leads, lead detail, editor.
- **FAQ:** free?, themes, maps key, lead storage, template override, agent logins, email delivery, uninstall, source.
- **Changelog:** Keep-a-Changelog style under `== Changelog ==`.
- **Demo site:** demo.wpbranddigital.org with sample content plus a reset every 24 h.
- **Docs:** getting started, blocks reference, CSV format, templates and hooks (`wbdres_listing_query_args`, `wbdres_property_card_html`, `wbdres_property_schema`, `wbdres_format_price`, `wbdres_pre_insert_lead`, `wbdres_email_args`, actions `wbdres_lead_created`, `wbdres_lead_status_changed`, `wbdres_lead_assigned`, `wbdres_property_submitted`, `wbdres_property_rejected`).
- **Support:** forum checked daily; 48 h first response; known issues pinned.
- **Upsell:** none inside wp-admin at launch; later a single dismissible notice on the plugin's own Tools screen only.
- **Disclosure:** OpenStreetMap section included in the readme.

**Guideline checklist.** GPL ✓ · no tracking ✓ · no external calls without disclosure ✓ · no locked features ✓ · prefixed functions, options and handles (`wbdres`) ✓ · sanitize/escape/nonce ✓ · no remote code ✓ · admin notices dismissible and limited to plugin screens ✓ · human-readable source (see the note on `src/` in §20 H) ✓.

---

## 19. 90-day organic marketing ($0 budget)

| Weeks | Activities | Content topics | KPI target |
|---|---|---|---|
| 1–2 | Demo site, docs, 5 readme keywords, launch post | "Build a real estate site with block themes in 30 minutes" | Plugin approved; 50 installs |
| 3–4 | 2 YouTube tutorials, LinkedIn launch carousel, r/WordPress Show-and-Tell (rules respected) | Setup walkthrough; CSV import | 150 installs, 3 reviews |
| 5–6 | Comparison pages (honest, dated) | "Free real estate plugins compared (2026)" | 300 installs |
| 7–8 | Agent-focused posts; Facebook groups for agents/WP freelancers (value posts, no spam) | "Lead follow-up system for small agencies" | 10 support threads resolved |
| 9–10 | Guest post on a WP blog, podcast pitch | "Interactivity API in practice" | 500 installs |
| 11–12 | Feedback survey, roadmap post, 1.1 release | Map view and agent registration | 4.8★ average, 15 reviews |

Weekly rhythm: 1 blog post, 1 short video, 3 LinkedIn posts, daily forum support, monthly changelog post. Reviews are asked for only after a solved support thread, never incentivised.

---

## 20. Final deliverables index

A PRD: §§1–2. B Feature matrix: §2. C Blocks: §3. D Forms: §4. E Admin: §5. F Templates: §6–7. G Database: §8. H Folder structure: §9. **Note:** the WordPress.org ZIP ships `build/` without `src/`, so publish the source on a public GitHub repo and link it in the readme before submitting. I Free vs Pro: §10. J Pricing: §11. K MVP: §1. L Development roadmap: §16. M Marketing: §19.

**N. Risk register**

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| WordPress.org review delays or requests | Med | Med | PHPCS-clean, disclosures, public source repo |
| Theme CSS conflicts | Med | Med | Scoped classes, hardened buttons, accent setting, template overrides |
| Email deliverability | High | Med | Timeline logging, test button, SMTP guidance |
| Spam floods | Med | Med | Honeypot, time trap, rate limit, `wbdres_pre_insert_lead` filter for external checks |
| Large catalogs slow down | Low | High | Indexed meta keys, caching, future lookup table |
| Core API changes (Interactivity, React 19) | Med | Med | Uses stable APIs only (`withSyncEvent`, `PluginDocumentSettingPanel` from `@wordpress/editor`); test against RCs |
| Solo-developer bandwidth | High | High | Everything free reduces license support; docs-first; strict scope |
| Legal / GDPR complaints | Low | High | Consent, retention, export/erase, no raw IPs |

**O. Prioritised backlog (next)**

| # | User story | Acceptance criteria | P |
|---|---|---|---|
| 1 | As a visitor I want a map view of search results | Toggle Grid/List/Map; markers for results with coordinates; consent respected; bundled Leaflet loaded only on demand | P1 |
| 2 | As an agent I want to register myself | Registration form → pending user → admin approval email → agent profile created | P1 |
| 3 | As a visitor I want saved-search alerts | Save the current filters with email; daily digest of new matches; one-click unsubscribe | P2 |
| 4 | As an admin I want a frontend agent dashboard | Agents see their own leads and listings on the frontend | P2 |
| 5 | As an agency I want Zapier/webhooks | Signed webhook on lead created and status changed; retry log | P2 |
| 6 | As an admin I want open-house events | Event date/time on a property; badge; "Open houses" listing variation | P2 |

**Practical implementation order (followed in this build):** bootstrap → settings → installer/roles → CPTs/taxonomies → meta → view layer → query/search → listings → templates → leads repository/service → REST → emails → admin screens → blocks → editor sidebar → interactivity → styles → patterns → privacy/schema → import/export → E2E tests → PHPCS → assets → readme → package.
