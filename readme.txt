=== TutorPress Lite for Tutor LMS ===
Contributors: indigetal
Tags: tutor lms, lms, gutenberg, courses
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: tutor
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

TutorPress settings for Tutor LMS: Gutenberg redirects, lesson discussion tabs, and co-instructor editing—without the premium Course Builder.

== Description ==

**TutorPress Lite for Tutor LMS** is the free WordPress.org companion to [TutorPress](https://indigetal.com/tutorpress). It uses the same `tutorpress_settings` option as the full plugin, so you can start on Lite and upgrade without re-entering preferences.

= What Lite includes =

* **Shared settings** — Five TutorPress toggles (discussion tab, dashboard links, admin/dashboard Gutenberg redirects, hide frontend builder button)
* **Lessons admin menu** — Quick access to lessons under Tutor LMS in wp-admin
* **Co-instructor editing** — Co-instructors can open and save shared courses, lessons, assignments, and quizzes in the block editor
* **Gutenberg redirects** — Optional backend and frontend dashboard links that open core post editors instead of Tutor’s builder screens
* **Lesson discussion tab** — Course Content / Discussion tabs in the lesson sidebar

= What requires full TutorPress =

* Gutenberg Course Builder (curriculum metabox, course settings panels, quiz modals, React admin bundle)
* Template hierarchy overrides
* Premium integrations shipped with the paid plugin

Lite is a complete product for its stated scope: native WordPress/Gutenberg course authoring with Tutor LMS, not a trial or feature-locked stub.

= Requirements =

* [Tutor LMS](https://wordpress.org/plugins/tutor/) (plugin slug: `tutor`)
* WordPress 6.0+
* PHP 7.4+

= Upgrade path =

1. Configure toggles in **Tutor LMS → TutorPress Lite**.
2. Install and activate the **full version of TutorPress** when you need the Course Builder (from Indigetal WebCraft, not WordPress.org).
3. Deactivate TutorPress Lite (your `tutorpress_settings` values are kept).

Do not run Lite and full TutorPress together; Lite shows a dismissible admin notice if both are active.

== Installation ==

1. Install and activate **Tutor LMS**.
2. Upload and activate **TutorPress Lite for Tutor LMS**.
3. Open **Tutor LMS → TutorPress Lite** to configure features.
4. (Optional) Enable admin or dashboard redirect toggles to use Gutenberg for course editing flows.

== Frequently Asked Questions ==

= Is this the same as full TutorPress? =

No. TutorPress Lite shares the same settings and UX improvements as the full version. Full TutorPress adds the Gutenberg Course Builder and template hierarchy tools that are not included in Lite.

= Will my settings survive if I upgrade to full TutorPress? =

Yes. Both plugins read and write the `tutorpress_settings` option. Deactivating Lite and activating full TutorPress keeps your toggle values.

= Does Lite delete settings on uninstall? =

No. Uninstall removes only Lite-specific housekeeping options (`tutorpress_lite_version`, capability migration version). Your TutorPress settings stay in the database for a future install or upgrade.

= Can co-instructors edit courses in Gutenberg? =

Yes, when co-instructors are assigned on a shared course in Tutor LMS. Lite corrects Tutor’s access checks and grants the capabilities needed for the block editor.

== Screenshots ==

1. TutorPress Lite settings page under Tutor LMS (five toggles)
2. Lessons submenu in the Tutor LMS admin menu
3. Lesson sidebar with Course Content and Discussion tabs
4. Tutor courses list with backend redirects enabled (Gutenberg post editor targets)

== Changelog ==

= 1.0.3 =
* WordPress.org submission package update

= 1.0.2 =
* WordPress.org review fixes: enqueue admin CSS, sanitize lesson sidebar HTML output, and add release packaging workflow
* Settings page copy updates for plugin directory guidelines
* Text domain updated to `tutorpress-lite-for-tutor-lms` to match the WordPress.org plugin slug

= 1.0.1 =
* Preserve lesson featured images and exercise attachments during Gutenberg/backend saves when those fields are omitted

= 1.0.0 =
* Initial WordPress.org release
* Shared `tutorpress_settings` with five feature toggles
* Lessons admin submenu, co-instructor collaborative editing, admin/dashboard Gutenberg redirects
* Lesson sidebar discussion tab
* Assignment CPT admin/REST support for co-instructors

== Upgrade Notice ==

= 1.0.3 =
Updated submission package for WordPress.org review.

= 1.0.2 =
WordPress.org compliance and packaging improvements for the 1.0.2 release.

= 1.0.1 =
Fixes Gutenberg lesson saves accidentally removing Tutor LMS featured images and exercise attachments.

= 1.0.0 =
Initial public release of TutorPress Lite for Tutor LMS.
