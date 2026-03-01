=== Luxembourg Citizenship Quiz ===
Contributors: Matthew J. Foster
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, shortcode-based quiz to help users navigate the pathways and determine their eligibility for citizenship in Luxembourg.

== Description ==

The Luxembourg Citizenship Quiz provides an interactive decision tree to guide users through the complex nationality laws. It includes paths for Article 7 (Direct Descent) and Article 23 (Option / Posthumous Recognition).

Features:
* Shortcode implementation: easily place the quiz anywhere using [luxembourg_eligibility_quiz]
* Dynamic, asynchronous interface built with Vanilla JavaScript (no page reloads)
* Generation-by-generation ancestry tracing (up to five generations)
* Built-in email functionality for users to send themselves their results
* Progress bar tracking
* Ministry of Justice disclaimer and soft member CTA on all outcome screens
* Clean, class-based HTML structure ready for your custom CSS

== Installation ==

1. Upload the `luxembourg-citizenship-quiz` directory to the `/wp-content/plugins/` directory, or upload the generated `.zip` file directly through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Place the `[luxembourg_eligibility_quiz]` shortcode on any page, post, or widget where you want the quiz to appear.

== Changelog ==

= 1.4 =
* Privacy update: replaced all birth-year number inputs with a single "Born before 1969?" checkbox per handoff brief.
* State shape updated: year (number) → bornBefore1969 (boolean) in both state.lineage entries and state.userBornBefore1969.
* evaluateEligibility() updated: child.year < 1969 → child.bornBefore1969 (boolean flag, no identifiable date collected).
* start step: replaced birth-year number input with informational context + Born-before-1969 checkbox.
* generation_loop step: replaced Birth Year number input with Born-before-1969 checkbox; no mandatory field, always submittable.
* Added .cq_checkbox_label and .cq_checkbox CSS classes; accent-color: var(--c-crimson).
* Container and input border-radius now respects --radius CSS variable (inherits 8px from theme).
* Restart button correctly resets state.userBornBefore1969 instead of state.userYear.

= 1.3 =
* Removed naturalization step from quiz flow; moved to outcome disclaimer per updated design brief.
* Re-added email results capture UI, wired to existing AJAX handler (lcq_send_results).
* Fixed back-button state reset bug: userYear and isAdopted now correctly reset when history empties.
* Added graceful "outcome_too_deep" outcome for lineages exceeding five generations.
* Rewrote all outcome copy with accurate Article 7 and Article 23 pathway details sourced from TCLAS guide.
* Added Ministry of Justice disclaimer (with link) to all outcome screens.
* Added soft TCLAS member CTA box to qualifying outcome screens (Article 7 and Article 23).
* Updated CSS palette fallback values to current Ciel Bleu design tokens (#0A2540, #EBF5FC, #E31E26).
* Added box-sizing reset, label hint styles, and scoped font-family on buttons.

= 1.2 =
* Refactored quiz engine to trace ancestry generation-by-generation (generation_loop).
* Added naturalization year check to evaluation logic.
* Added form inputs (year, gender, birth country) to generation collection step.
* Introduced state object and history array for back-navigation.

= 1.1 =
* Added built-in email functionality for users to send themselves their results via AJAX.

= 1.0 =
* Initial release. Features dynamic decision tree and progress bar.
