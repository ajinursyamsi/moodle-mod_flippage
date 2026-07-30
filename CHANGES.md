# Change log

## 2026073000

- Move the viewer JavaScript to a Moodle AMD module.
- Replace the legacy progress endpoint with a Moodle External Service.
- Add activity backup and restore support.
- Complete Moodle boilerplate headers in plugin source files.
- Load PDF.js through a native ES module bridge to avoid RequireJS no-define errors.

## 2026072500

- Add upgrade script required by Moodle plugin validation.

## 2026072408

- Initial public release candidate.
- Add PDF-only flipbook activity.
- Add PDF.js and StPageFlip as bundled local third-party libraries.
- Add final-page activity completion rule.
- Add learner access limit and progress tracking.
- Add zoom controls and end-of-activity exit button.
- Add Moodle Privacy API support for stored learner progress.
