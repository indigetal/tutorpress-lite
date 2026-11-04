=== Indigetal Course Workflow Enhancements for Tutor LMS ===
(Internal development readme — not shipped in release zips. The WordPress.org plugin directory readme is wordpress-org/trunk/readme.txt.)

Contributors: indigetal
Tags: tutor lms, lms, gutenberg, courses
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: tutor
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

TutorPress settings for Tutor LMS: Gutenberg redirects, lesson discussion tabs, and co-instructor editing—without the premium Course Builder.

== Description ==

**Indigetal Course Workflow Enhancements for Tutor LMS** is the free WordPress.org companion to [TutorPress](https://indigetal.com/tutorpress). It uses the same `tutorpress_settings` option as the full plugin, so you can start here and upgrade to full TutorPress without re-entering preferences.

= What this plugin includes =

* **Shared settings** — Five TutorPress toggles (discussion tab, dashboard links, admin/dashboard Gutenberg redirects, hide frontend builder button)
* **Lessons admin menu** — Quick access to lessons under Tutor LMS in wp-admin
* **Co-instructor editing** — Co-instructors can open and save shared courses, lessons, assignments, and quizzes in the block editor
* **Gutenberg redirects** — Optional backend and frontend dashboard links that open core post editors instead of Tutor’s builder screens
* **Lesson discussion tab** — Course Content / Discussion tabs in the lesson sidebar

= What requires full TutorPress =

* Gutenberg Course Builder (curriculum metabox, course settings panels, quiz modals, React admin bundle)
* Template hierarchy overrides
* Premium integrations shipped with the paid plugin

This plugin is a complete product for its stated scope: native WordPress/Gutenberg course authoring with Tutor LMS, not a trial or feature-locked stub.

= Requirements =

* [Tutor LMS](https://wordpress.org/plugins/tutor/) (plugin slug: `tutor`)
* WordPress 6.0+
* PHP 7.4+

= Upgrade path =

1. Configure toggles in **Tutor LMS → Course Workflow**.
2. Install and activate the **full version of TutorPress** when you need the Course Builder (from Indigetal WebCraft, not WordPress.org).
3. Deactivate Indigetal Course Workflow Enhancements for Tutor LMS (your `tutorpress_settings` values are kept).

Do not run this plugin and full TutorPress together; the plugin shows a dismissible admin notice if both are active.

== Installation ==

1. Install and activate **Tutor LMS**.
2. Upload and activate **Indigetal Course Workflow Enhancements for Tutor LMS**.
3. Open **Tutor LMS → Course Workflow** to configure features.
4. (Optional) Enable admin or dashboard redirect toggles to use Gutenberg for course editing flows.

== Frequently Asked Questions ==

= Is this the same as full TutorPress? =

No. Indigetal Course Workflow Enhancements for Tutor LMS shares the same settings and UX improvements as the full version. Full TutorPress adds the Gutenberg Course Builder and template hierarchy tools that are not included in this plugin.

= Will my settings survive if I upgrade to full TutorPress? =

Yes. Both plugins read and write the `tutorpress_settings` option. Deactivating this plugin and activating full TutorPress keeps your toggle values.

= Does this plugin delete settings on uninstall? =

No. Uninstall removes only plugin-specific housekeeping options (`tutorpress_lite_version`, capability migration version). Your TutorPress settings stay in the database for a future install or upgrade.

= Can co-instructors edit courses in Gutenberg? =

Yes, when co-instructors are assigned on a shared course in Tutor LMS. This plugin corrects Tutor’s access checks and grants the capabilities needed for the block editor.

== Screenshots ==

1. Course Workflow under Tutor LMS in wp-admin — entry point for the five TutorPress settings toggles
2. Media Library and H5P links added to the frontend instructor dashboard menu
3. Lesson Discussion tab in the lesson sidebar (standard WordPress comment patterns)

== Changelog ==

= 1.0.6 =
* Co-instructor editing: Lesson and Assignment edit and delete access now follows the parent Course's instructors; authoring an item no longer grants access on its own.
* Course Bundles: only administrators and the Bundle's author can edit or delete a Bundle.
* Instructors no longer hold the edit-others and delete-others Assignment capabilities; existing sites have them removed once after updating.

= 1.0.5 =
* Tutor LMS 4.0 compatibility for dashboard Gutenberg redirects and lesson Discussion tab
* Support modern, kids, and legacy learning modes while preserving Tutor LMS <4.0 behavior

= 1.0.4 =
* Renamed plugin to Indigetal Course Workflow Enhancements for Tutor LMS for WordPress.org naming compliance
* Text domain updated to `indigetal-course-workflow-enhancements-for-tutor-lms`

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

= 1.0.6 =
Tightens co-instructor permissions for Lessons, Assignments, and Course Bundles, and removes instructors' edit-others and delete-others Assignment capabilities.

= 1.0.5 =
Tutor LMS 4.0 compatibility for dashboard redirects and lesson Discussion tabs.

= 1.0.4 =
Renamed for WordPress.org compliance. Settings and upgrade path to full TutorPress are unchanged.

= 1.0.3 =
Updated submission package for WordPress.org review.

= 1.0.2 =
WordPress.org compliance and packaging improvements for the 1.0.2 release.

= 1.0.1 =
Fixes Gutenberg lesson saves accidentally removing Tutor LMS featured images and exercise attachments.

= 1.0.0 =
Initial public release as TutorPress Lite for Tutor LMS.
