import { test as base, expect, type Locator, type Page, type Request } from '@playwright/test';

// Shared fixtures and helpers for the namedlinkfield specs.
//
// The CMS screen is the fixture ModelAdmin in tests/browser/fixtures/ (copied into the scratch host
// by the runner): /admin/nl-browser/records, a GridField of NlBRecord rows whose edit form carries
// one scaffolded NamedLinkFormField named "Link". Every dev/build re-seeds one record per spec
// (fixtures/NlBRecord.php), so the specs never share a row.

/**
 * test, extended with an automatic console guard: every spec fails if the page logs a console
 * error or throws an uncaught exception at any point, page load included. "Failed to load
 * resource" (any 4xx/5xx asset or request) arrives as a console error too, so a missing module
 * bundle or stylesheet is caught here as well. Warnings (the admin's own Apollo deprecation
 * notices) do not count.
 */
export const test = base.extend<{ consoleGuard: void }>({
    consoleGuard: [
        async ({ page }, use, testInfo) => {
            const errors: string[] = [];
            page.on('console', (msg) => {
                if (msg.type() === 'error') {
                    errors.push(`console.error: ${msg.text()} (${msg.location().url})`);
                }
            });
            page.on('pageerror', (err) => errors.push(`uncaught: ${err.message}`));

            await use();

            if (errors.length) {
                await testInfo.attach('console-errors', { body: errors.join('\n'), contentType: 'text/plain' });
            }
            expect(errors, 'no console errors or uncaught exceptions').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

/** The five link types, as the Linkmode dropdown lists them (value = label). */
export const MODES = ['Page', 'URL', 'File', 'Email', 'Shortcode'] as const;
export type Mode = (typeof MODES)[number];

/** The sub-field wrappers of the NamedLinkFormField template (templates/NamedLinkFormField.ss). */
export type Part = 'PageID' | 'PageAnchor' | 'FileID' | 'CustomURL' | 'Shortcode';
export const PARTS: Part[] = ['PageID', 'PageAnchor', 'FileID', 'CustomURL', 'Shortcode'];

/** Which wrappers the entwine script (client/src/js/legacy) shows for each link type. */
export const VISIBLE_FOR: Record<Mode, Part[]> = {
    Page: ['PageID', 'PageAnchor'],
    URL: ['CustomURL'],
    File: ['FileID'],
    Email: ['CustomURL'],
    Shortcode: ['Shortcode'],
};

/** The field's wrapper in the record's edit form. */
export function linkField(page: Page): Locator {
    return page.locator('#Form_ItemEditForm .LinkFormField');
}

/** One sub-field wrapper of the field, e.g. part(page, 'CustomURL') = .LinkFormFieldCustomURL. */
export function part(page: Page, name: Part): Locator {
    return linkField(page).locator(`.LinkFormField${name}`);
}

/**
 * Open a seeded record's edit form from the ModelAdmin list, the way an editor does: a full load of
 * the list, then a click on the row (the CMS loads the form through its own XHR request).
 */
export async function openRecord(page: Page, title: string): Promise<void> {
    await page.goto('/admin/nl-browser/records');
    const row = page.locator('#Form_EditForm_records tr.ss-gridfield-item').filter({
        has: page.locator('td.col-Title', { hasText: new RegExp(`^\\s*${title}\\s*$`) }),
    });
    await expect(row).toHaveCount(1);
    await row.click();
    await expect(linkField(page)).toBeVisible();
}

/**
 * Pick a link type the way an editor does. The admin turns the Linkmode <select> into a "chosen"
 * widget and hides the select itself, so this clicks the widget and then the option; chosen then
 * fires the select's change event, which the module's entwine script listens for.
 */
export async function chooseMode(page: Page, mode: Mode): Promise<void> {
    const chosen = page.locator('#Form_ItemEditForm_LinkLinkmode_chosen');
    await chosen.locator('a.chosen-single').click();
    await chosen.locator('li.active-result', { hasText: new RegExp(`^\\s*${mode}\\s*$`) }).click();
    await expect(page.locator('#Form_ItemEditForm_LinkLinkmode')).toHaveValue(mode);
}

/** Pick an option of the page-anchor dropdown, through its chosen widget like chooseMode(). */
export async function chooseAnchor(page: Page, label: string): Promise<void> {
    const chosen = page.locator('#Form_ItemEditForm_LinkPageAnchor_chosen');
    await chosen.locator('a.chosen-single').click();
    await chosen.locator('li.active-result', { hasText: new RegExp(`^\\s*${label}\\s*$`) }).click();
}

/** Assert that exactly the wrappers for this link type are shown, and all others hidden. */
export async function expectVisibleFor(page: Page, mode: Mode): Promise<void> {
    for (const name of PARTS) {
        const shown = VISIBLE_FOR[mode].includes(name);
        const locator = part(page, name);
        if (shown) {
            await expect(locator, `${name} is shown for ${mode}`).toBeVisible();
        } else {
            await expect(locator, `${name} is hidden for ${mode}`).toBeHidden();
        }
    }
}

/**
 * Record every DOCUMENT request of the main frame from now on. A save must go through the CMS's
 * own XHR request, never by replacing the page. Returns a getter for the URLs seen.
 */
export function watchDocumentNavigations(page: Page): () => string[] {
    const seen: string[] = [];
    page.on('request', (r) => {
        if (r.isNavigationRequest() && r.frame() === page.mainFrame()) {
            seen.push(`${r.method()} ${r.url()}`);
        }
    });
    return () => [...seen];
}

/** Assert a request was an AJAX request answered with 200 (body excerpt in the message if not). */
export async function expectAjaxOk(request: Request, what: string): Promise<void> {
    expect(['xhr', 'fetch'], `${what} is an AJAX request`).toContain(request.resourceType());
    const response = await request.response();
    const status = response?.status();
    const body = status === 200 ? '' : ((await response?.text().catch(() => '')) ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 1500);
    expect(status, `${what} status${body ? `; body: ${body}` : ''}`).toBe(200);
}

/**
 * Click the edit form's Save button and wait for the save round trip: an AJAX POST of the item
 * form answered with 200, no document navigation, and the CMS's "Saved" toast.
 */
export async function save(page: Page): Promise<void> {
    const navigations = watchDocumentNavigations(page);
    const posted = page.waitForRequest((r) => r.method() === 'POST' && /\/ItemEditForm(\?|$)/.test(r.url()));
    await page.locator('#Form_ItemEditForm button[name="action_doSave"]').click();
    await expectAjaxOk(await posted, 'the save');
    await expect(page.locator('#Form_ItemEditForm .message.good, .toast, .notice-item').first()).toBeVisible();
    expect(navigations(), 'document navigations during the save').toEqual([]);
}
