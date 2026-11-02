# Changelog

## 2.1.6 (2026-11-02)

### Fixed

- `$Link.URL` no longer escapes an `&` twice (it reached the page as `&amp;amp;`) (#52).
  `getURL()` returns the raw URL from every link type, and the template escapes it exactly once;
  `$Link.URL.RAW` gives the URL as stored.
- The scaffolded CMS field takes the title it is given, and adding a `NamedLinkFormField` under the
  name of the scaffolded one replaces it instead of throwing "a field called '<name>Title' appears
  twice" (#53). Placing or labelling the field no longer needs `removeByName()` first.
- `composer.json` now requires `silverstripe/cms` (`^4 || ^5`) and `silverstripe/admin` (`^1 || ^2`),
  which the field needs: it builds its page picker on `SiteTree` (#55). Every install that could
  use the field already had both, so no Silverstripe 4/5 install is dropped.

### Changed

- **PHP callers of `getURL()`** now receive the raw URL (before: HTML-escaped for the URL, Page and
  Email link types). Code that echoes it straight into HTML without escaping must escape it itself;
  templates need no change.

## 2.1.5 (2026-10-08)

### Fixed

- Picking a page in the Page tree now reloads the text-anchor dropdown with that page's anchors,
  without saving and reopening the record first (#43). DependentDropdownField bound its change
  handler to the TreeDropdownField's server-rendered placeholder input, which the admin's React
  render replaces; the module now listens on the live input (`client/src/js/legacy/LinkFormField.entwine.js`).
