# Changelog

## 2.1.5 (2026-10-08)

### Fixed

- Picking a page in the Page tree now reloads the text-anchor dropdown with that page's anchors,
  without saving and reopening the record first (#43). DependentDropdownField bound its change
  handler to the TreeDropdownField's server-rendered placeholder input, which the admin's React
  render replaces; the module now listens on the live input (`client/src/js/legacy/LinkFormField.entwine.js`).
