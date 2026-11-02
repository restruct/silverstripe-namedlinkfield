# README screenshots

`readme.shot.ts` writes the PNGs in `docs/images/` that the module README shows. It is not a test
suite: it runs with its own config, `playwright.screenshots.config.ts`, which the browser tests and
CI never load, so a normal test run never rewrites the images. Never edit the PNGs by hand;
regenerate them.

What it needs:

- a Silverstripe 6 host with this module installed and the browser fixtures from
  `tests/browser/fixtures/` copied into its `app/src/`, then the database built. `NlBShowcasePage`
  seeds the content: the "Open day" page with four links (page with anchor, URL, file, e-mail), the
  "Visit us" page they point at, and `Brochures/visitor-guide.pdf` in Files;
- `BROWSER_TARGET_URLS` pointing at that host (only the `ss6` target is used, else the first).

The shared runner in the maintainers' workspace does all of that:

```bash
BROWSER_ONLY=ss6 tools/browser/run.sh silverstripe-namedlinkfield --config=playwright.screenshots.config.ts
```

Against a host you started yourself, from the `tests/browser/` directory:

```bash
npm ci
BROWSER_TARGET_URLS="ss6=http://127.0.0.1:8894" npx playwright test --config=playwright.screenshots.config.ts
```

The script hides what only the test host has (the specs' ModelAdmin menu entry and their
"NlB anchor page"); it changes nothing about the module's own UI.
