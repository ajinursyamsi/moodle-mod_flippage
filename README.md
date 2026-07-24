# Flip page

Flip page is a Moodle activity module for presenting a PDF as an interactive flipbook.

Teachers upload a single PDF file, and learners read it directly inside the course page with page-turning controls, zoom controls, reading progress tracking, and an optional completion condition when the final page is reached.

The plugin is intentionally focused on PDF files. This keeps the activity predictable for teachers and avoids server-side document conversion requirements for Word or PowerPoint files.

## Main features

- Upload one PDF file per activity.
- Render PDF pages in the browser using bundled PDF.js.
- Display the rendered pages as a flipbook using bundled StPageFlip.
- Provide previous page, next page, zoom in, zoom out, and reset zoom controls.
- Show an Exit activity button when the learner reaches the final page.
- Track learner access count, latest page reached, total pages, first access, latest access, and final-page completion status.
- Optionally limit the number of learner accesses.
- Support Moodle automatic activity completion when the learner reaches the final page.

## Requirements

- Moodle 4.0 or later.
- A modern browser with JavaScript enabled.

## Installation

1. Copy the `flippage` folder to `mod/flippage` in your Moodle installation.
2. Visit Site administration to complete the Moodle plugin upgrade.
3. Add a new Flip page activity to a course and upload a PDF file.

## Activity completion

Flip page supports Moodle's standard manual completion and a custom automatic completion rule:

- Student must reach the final page.

In landscape mode, the plugin treats the right-hand page in a two-page spread as visible. For example, if pages 29 and 30 are shown together, page 30 is counted as reached.

## Access limit

The Allowed accesses setting controls how many times a learner can open the activity. Set it to `0` for unlimited access. Users with the manage capability are not blocked by this limit.

## Privacy

The plugin stores learner reading progress in the `flippage_views` table:

- user id
- number of accesses
- latest page reached
- total page count
- completion flag
- first access time
- latest access time

This data is used only for activity progress, access limiting, and completion tracking. The plugin implements Moodle's Privacy API for metadata, export, and deletion of stored user progress.

## Third-party libraries

The plugin bundles its runtime libraries locally and does not load viewer assets from external CDNs.

- [PDF.js](https://mozilla.github.io/pdf.js/) 4.10.38, Apache License 2.0.
- [StPageFlip](https://github.com/Nodlik/StPageFlip) 2.0.7, MIT license.

See `thirdpartylibs.xml` for the bundled library declarations.

## License

This plugin is licensed under the GNU GPL v3 or later.
