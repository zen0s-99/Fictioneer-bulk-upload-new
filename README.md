=== Bulk Chapter Upload ===

Bulk upload chapters from a ZIP file for your stories, with advanced scheduling, password protection, and password expiration features. Designed to integrate with themes using the Fictioneer framework.

== Description ==

The "Bulk Chapter Upload" plugin allows authors and site administrators to efficiently add multiple chapters to their stories (custom post type 'fcn_story'). Instead of creating each chapter manually, you can package your chapter text files (.txt) into a single ZIP archive and upload them in one go.

**Key Features:**

*   **Bulk Upload via ZIP:** Upload a single ZIP file containing multiple .txt files. Each .txt file becomes a new chapter.
*   **Story Association:** Assign uploaded chapters to an existing story (requires 'fcn_story' post type).
*   **Chapter Status:** Create chapters as 'draft' or 'publish' them immediately.
*   **Filename-Based Ordering:** Chapters are processed based on filenames. For precise ordering, name your files ending with "C" followed by a number (e.g., `MyStory C1.txt`, `Chapter C002.txt`, `PartThree C10.txt`). Other files will be processed but their order might be less predictable.
*   **Advanced Scheduling:**
    *   **No Scheduling:** Upload chapters with the chosen status (draft/publish) immediately.
    *   **Single Date (All Chapters):** Schedule all chapters to be published starting from a specific date and time. Optionally, add a configurable delay (minutes, hours, days) between each subsequent chapter.
    *   **Weekly Schedule:** Select specific days of the week and a time for chapters to be published sequentially.
*   **Password Protection:**
    *   Optionally protect all uploaded chapters with a single password (minimum 4 characters).
*   **Password Expiration:**
    *   Set an expiration duration for the chapter password.
    *   The expiration is calculated by multiplying an "Expire In" value (minutes, hours, or days) by the chapter's sequence number in the upload batch.
    *   The base date for expiration can be the chapter's publication/scheduled date or a custom "Password Expiry Start Date" (if using "Single Date" scheduling).
*   **Category Assignment:** Assign one or more categories to all uploaded chapters simultaneously.
*   **Theme Integration:** Designed to work with themes that provide the `fictioneer_append_chapter_to_story` function for associating chapters with stories (e.g., Fictioneer theme framework). The plugin will show a warning if this function is not detected in the active theme.

This plugin streamlines the process of populating your story website with content, saving you significant time and effort.

== Installation ==

1.  Upload the plugin folder (e.g., `fictioneer-bulk-upload-new`) to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to the "Bulk Chapter Upload" menu item in your WordPress admin dashboard to start uploading chapters.

== Frequently Asked Questions ==

= What is the required format for the ZIP file? =

The ZIP file should contain plain text files with a `.txt` extension. Each `.txt` file will be treated as a separate chapter. The content of the text file will become the chapter content, and the filename (without the .txt extension) will become the chapter title.

= How does chapter ordering work? =

The plugin attempts to sort chapter files found in the ZIP archive before processing. For reliable and precise ordering, your `.txt` filenames **must** end with the letter "C" (case-insensitive) followed by one or more digits, and then `.txt`. For example:
*   `My Story C1.txt`
*   `Chapter C02.txt`
*   `Prologue C0.txt`
*   `SectionA C115.txt`

If filenames do not follow this `C<number>.txt` pattern, they will still be processed, but their order will depend on the default sorting of your server's file system, which is typically alphabetical but not guaranteed to be numerically natural for names like "Chapter 1.txt", "Chapter 10.txt".

= What happens if my theme doesn't have the `fictioneer_append_chapter_to_story` function? =

This plugin relies on a theme function, typically `fictioneer_append_chapter_to_story`, to correctly link the uploaded chapters to the selected story post type (`fcn_story`). If this function is not found in your active theme:
1.  Chapters will still be created (as `fcn_chapter` post types).
2.  They will **not** be automatically associated with the selected story.
3.  You will see a warning notice on the Bulk Chapter Upload page.
4.  You would need to manually associate these chapters with their stories or ensure your theme provides the necessary integration.

= How does password expiration work? =

If you set a password and an "Expire In" value:
1.  The "Expire In" value you provide (e.g., 5) and its unit (e.g., days) are used.
2.  This value is multiplied by the chapter's sequence number in the current upload batch (1 for the first chapter, 2 for the second, and so on).
    *   Example: If "Expire In" is 5 days:
        *   1st chapter password expires in (5 * 1) = 5 days from its base date.
        *   2nd chapter password expires in (5 * 2) = 10 days from its base date.
        *   3rd chapter password expires in (5 * 3) = 15 days from its base date.
3.  The "base date" for this calculation is either:
    *   The chapter's actual publication/scheduled date (GMT).
    *   OR, if you are using "Single Date" scheduling and have set a "Password Expiry Start Date", that specific date (GMT) is used as the base for all chapters in that batch.
4.  Password expiration only applies if a chapter password is set. If the password field is empty or an invalid password (less than 4 characters) is provided, expiration settings are ignored.

= Can I schedule chapters to be published on specific days of the week? =

Yes, using the "Weekly Schedule" option. You can select the days of the week (e.g., Monday, Wednesday, Friday) and a specific time. The plugin will then schedule the chapters sequentially on the next available selected day and time.

== Screenshots ==

1.  The main Bulk Chapter Upload form, showing options for story selection, status, ZIP file, scheduling, password, and expiration.
2.  Example of scheduling options for "Single Date" including delay and expiry start date.
3.  Example of "Weekly Schedule" options.

*(It's good practice to actually include these screenshots in a `/assets` subfolder of your plugin, named `screenshot-1.png`, `screenshot-2.png`, etc.)*

== Changelog ==

= 1.3 =
*   Refined password expiration logic for consistency and clarity.
*   Improved handling of theme function dependency (`fictioneer_append_chapter_to_story`) with `function_exists()` checks and admin notices.
*   Resolved potential double processing when chapters are published.
*   Enhanced UI descriptions for better user understanding of complex features.
*   Improved timezone handling for scheduling and expiration dates.
*   Robust error handling for invalid password input.
*   jQuery enhancement for schedule options visibility on page load.
*   Updated logging messages and file cleanup.
*   (Credit to zen0s for significant feature enhancements and refinements from original base)

= 1.2 (Modified by zen0s from sharnabeel's v1.0 base) =
*   Feature: Added password protection for uploaded chapters.
*   Feature: Added password expiration based on chapter sequence and configurable units (minutes, hours, days).
*   Feature: Added option for a specific "Password Expiry Start Date" when using single date scheduling.
*   Feature: Added option to assign categories to uploaded chapters.
*   Improvement: Implemented filename sorting for `C<number>.txt` convention.
*   Improvement: Refined scheduling options and UI.

= 1.0 (Base by sharnabeel) =
*   Initial release by sharnabeel.
*   Bulk upload chapters from ZIP.
*   Basic scheduling options (No scheduling, Single Date, Daily Increment, Weekly).
*   Associate chapters with 'fcn_story' post type.
*   Set chapter status (draft/publish).

== Upgrade Notice ==

= 1.3 =
This version includes significant refinements to scheduling, password expiration logic, and theme integration. Please review your settings if you use password expiration, as the calculation method has been clarified and made more consistent. It also improves error handling and UI clarity.

== Development ==

Development of this plugin takes place on GitHub:
[https://github.com/zen0s-99/Fictioneer-bulk-upload-new](https://github.com/zen0s-99/Fictioneer-bulk-upload-new)

Pull requests and issue reporting are welcome!



