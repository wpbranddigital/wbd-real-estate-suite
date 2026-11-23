=== WBD Real Estate Suite ===
Contributors: wpbranddigital
Tags: real estate, property listings, realtor, property management, real estate agent
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gutenberg-native property listings, search, agent profiles, inquiry forms and a built-in lead CRM. Lightweight, fast and 100% free.

== Description ==

**WBD Real Estate Suite** turns any WordPress site into a complete, modern real estate website — built natively for the block editor, with no jQuery, no page builder and no paid add-ons. Every feature is included for free.

It is made for independent agents, small agencies, property consultants, small developers and the freelancers who build their websites.

= Property management =

* Properties with price (or "price on request"), price suffix (e.g. /month), bedrooms, bathrooms (half baths supported), floor and land area, garages, year built, reference/MLS ID, address, coordinates, gallery, video and virtual tour.
* Property types, statuses (For Sale, For Rent, Sold…), hierarchical locations (country → city → area) and features/amenities.
* Featured listings, quick featured toggle and sortable admin columns.
* Clean editing in the block editor sidebar — no clunky meta boxes.
* Global ready: any currency symbol and position, separators, decimals, and area units (sq ft, m², acres, hectares, katha, marla, tsubo, ping).

= 14 Gutenberg blocks (plus variations) =

* **Property Listings** — grid or list, two card styles, query by type/status/location/agent, featured only, sorting, results toolbar, grid/list toggle, active filter chips and pagination. Variations: Featured, Latest, Search Results, Similar and Agent Properties.
* **Property Search** — horizontal search bar, sidebar filters or compact hero search with Buy/Rent tabs, price range, beds & baths, area and amenities. Works without JavaScript; filters live in shareable URLs.
* **Property Details** — the complete single-property layout in one block.
* **Property Field** — title, price, address, status badge, specifications, key facts, features, type or reference for custom designs.
* **Property Gallery** — hero photo grid with an accessible full-screen viewer (keyboard and screen-reader friendly).
* **Property Map** — OpenStreetMap, no API key, loads only after visitor consent.
* **Property Video** — YouTube, Vimeo or any oEmbed provider plus virtual tour link.
* **Agent Profile** and **Agent Directory**.
* **Property Inquiry Form** — inquiry, schedule-a-viewing and contact-agent variations.
* **Mortgage Calculator** — instant monthly payment estimate.
* **Saved Properties** — favorites, recently viewed and a side-by-side comparison table (stored privately in the visitor's browser).
* **Frontend Property Submission** — logged-in users submit properties for review and see their status in a "My properties" dashboard.
* **Browse by Type or Location** — clickable cards or chips with listing counts.

= Ready-made designs =

* Automatic single-property layout: photo grid with "View all photos", price, status, key facts, estimated monthly payment, favorite/compare/share buttons, "What's special" features, facts & features, map, payment calculator, sticky agent contact card, schedule-a-viewing, recommended properties and a mobile contact bar.
* Block-theme templates for the property archive, taxonomy archives, single property and single agent (fully editable in the Site Editor) and classic-theme fallbacks.
* 10 patterns: Real Estate Homepage, Property Search and Listing Page, Single Property Landing Page, Agent Directory, Agent Profile, Submission Page, Favorites, Comparison, Contact and CTA banner.
* One-click demo pages and removable sample content.
* Works with block themes and popular classic themes (tested with Twenty Twenty-Five, Twenty Twenty-Four, Astra, Kadence, GeneratePress and OceanWP).

= 2-minute setup wizard =

* Pick your country and the currency, number format and area unit are set for you (US, Bangladesh, India, Pakistan, UAE, Saudi Arabia, UK, Eurozone, Canada, Australia, Malaysia, South Africa or custom).
* Add your agency, phone and WhatsApp number — your agent profile is created automatically.
* Create all website pages, add sample listings and set the homepage in one click.

= WhatsApp click-to-chat =

* WhatsApp buttons on agent cards, property pages and the mobile contact bar.
* The message is pre-filled with the property title and link, so agents know exactly which listing the buyer means. Placeholders: {title}, {url}, {price}, {reference}.
* Each agent can use their own number, with a site-wide fallback number.
* Optional floating WhatsApp button on property pages or on every page.

= Lead management (built-in CRM) =

* Every inquiry, viewing request and contact message is stored as a lead.
* Pipeline stages: New, Contacted, Qualified, Viewing scheduled, Negotiation, Won, Lost.
* Assign leads to agents, add notes, see a full activity timeline, bulk actions, search, filters and CSV export.
* Agents linked to a WordPress user see only their own leads.
* Overview dashboard with KPI cards, lead funnel, leads by type, upcoming viewings, recent inquiries and most viewed properties.
* Analytics: leads over time, pipeline by stage, lead sources and top properties — all data stays on your site.

= Email notifications =

* Admin and agent notifications, visitor auto-reply, lead assignment, submission received, approved and rejected emails.
* Editable subjects and messages with placeholders, test email button and delivery results on each lead's timeline.
* Works with any SMTP plugin (uses `wp_mail()`).

= Import & export =

* CSV import with header mapping, dry-run preview, validation report, duplicate detection by reference, optional image download and one-click undo.
* CSV export of properties and leads.

= Built right =

* Gutenberg-first: block.json, dynamic server-rendered blocks, the Interactivity API and native components.
* No jQuery, no external services required, no tracking.
* Fast: assets load only where needed, lazy-loaded responsive images, minimal CSS and a ~5 KB interactive script module.
* Secure: capability checks, nonces, sanitization and escaping everywhere, prepared SQL, honeypot + time-trap + rate-limit spam protection, no raw IP storage.
* Private: GDPR consent checkbox, privacy policy text, personal data export and erase, optional lead retention period.
* Accessible: semantic HTML, labelled controls, focus states, keyboard support and reduced-motion support.
* SEO: schema.org `RealEstateListing` structured data, clean URLs, and `noindex` on filtered result pages to avoid duplicate content.
* Translation-ready and RTL-ready.

== Development ==

Public source code repository:
https://github.com/wpbranddigital/wbd-real-estate-suite

Build Instructions:

1. npm install
2. npm run build
3. npm run start (development)
wbd real estate suite is built using @wordpress/scripts.

== Installation ==

1. Install and activate the plugin from **Plugins → Add New**, or upload the ZIP.
2. Click **Run the 2-minute setup wizard** in the welcome notice (or open **Real Estate → Setup Wizard**).
3. Choose your country, enter your agency contact and WhatsApp number, and let the wizard create your pages.
4. Add your own properties under **Real Estate → Add New Property** and remove the sample listings in **Real Estate → Tools** when you go live.
5. Your listings appear at `/properties/` automatically. Use the blocks and patterns to build any page.

== Frequently Asked Questions ==

= Is everything really free? =

Yes. All features described here are included in this plugin at no cost.

= Does it work with my theme? =

It works with block themes (templates are included and editable in the Site Editor) and with classic themes (automatic layouts and an overridable archive template). The design inherits your theme's fonts and you can choose an accent colour.

= Do I need a Google Maps API key? =

No. Maps use OpenStreetMap and load only after the visitor clicks "Load map" (you can turn the consent step off).

= Where are leads stored? =

In two custom database tables on your own site. Leads are never sent to us or to any third party.

= How do I override the archive template in a classic theme? =

Copy `templates/archive-property.php` to `your-theme/wbd-real-estate-suite/archive-property.php` and edit it.

= Can agents log in and see their leads? =

Yes. Create a user with the "Real Estate Agent" role and link it to an agent profile in the agent editor sidebar.

= Will emails be delivered? =

The plugin uses `wp_mail()`. For reliable delivery we recommend an SMTP plugin. Use the "Send test email" button in Settings → Email Templates.

= What happens to my data if I delete the plugin? =

Nothing is deleted unless you enable "Delete all data on uninstall" in Settings → Advanced.

= Is the source code available? =

Yes. The human-readable JavaScript and SCSS source and build configuration (`@wordpress/scripts`) are in the `src` folder of the source package; run `npm install` and `npm run build` to rebuild the `build` folder.

== External services ==

This plugin can embed maps from **OpenStreetMap** on property pages that have coordinates.

* What is sent: when a visitor views a property map, their browser requests map tiles from openstreetmap.org, which receives the visitor's IP address and the map area (property coordinates).
* When: only after the visitor clicks "Load map" (default), or on page load if the site owner disables the consent step in Settings → Display. Maps can be disabled entirely.
* Service provider: OpenStreetMap Foundation — [Terms of use](https://wiki.osmfoundation.org/wiki/Terms_of_Use), [Privacy policy](https://osmfoundation.org/wiki/Privacy_Policy).

Videos you add to properties are embedded through WordPress' standard oEmbed feature (for example YouTube or Vimeo) and are subject to those providers' terms.

WhatsApp buttons are plain links to **WhatsApp** (wa.me). Nothing is loaded from or sent to WhatsApp when a page is viewed. Only when a visitor clicks a WhatsApp button does their browser open wa.me with the agent's number and the pre-filled message (property title and link); from then on WhatsApp's [Terms of Service](https://www.whatsapp.com/legal/terms-of-service) and [Privacy Policy](https://www.whatsapp.com/legal/privacy-policy) apply. WhatsApp buttons appear only when you enter a WhatsApp number.

No other external requests are made. The plugin does not collect usage data.

== Screenshots ==

1. Real estate homepage pattern with hero search, property types and featured listings.
2. Property archive with search bar, results toolbar, sorting and grid view.
3. Search page with sidebar filters (type, location, price, rooms, amenities).
4. Single property page with photo grid, key facts, features and sticky agent contact card.
5. Mobile views: search, listings and single property with contact bar.
6. Property comparison table and saved favorites.
7. Admin overview dashboard with KPIs, lead funnel, leads by type and upcoming viewings.
8. Leads CRM list with pipeline stages, filters and bulk actions.
9. Lead detail with activity timeline, stage and agent assignment.
10. Property editing in the block editor with the details sidebar.
11. Setup wizard: choose your region and currency, add your agency and create all pages.
12. WhatsApp click-to-chat with the property link pre-filled, on mobile.

== Changelog ==

= 1.0.0 =
* Initial release.
* Setup wizard with regional presets, agency profile and one-click pages.
* WhatsApp click-to-chat with pre-filled property message and optional floating button.
* Responsive and typography polish for all patterns; classic-theme compatibility fixes.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
