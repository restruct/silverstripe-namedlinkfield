import { test, expect, chooseAnchor, chooseMode, linkField, openRecord, part, save } from './support';

// Page anchors: for a Page link, the anchor dropdown (a DependentDropdownField fed by
// NamedLinkCtrl::get_page_anchors) offers the id= and name= anchors in the page's Content. The
// fixture's anchor page carries id="intro" and name='contact' (fixtures/NlBRecord.php).

/** The ID of the seeded anchor page, read from the Page record's tree dropdown. */
async function anchorPageId(page: import('@playwright/test').Page): Promise<number> {
    return Number(await page.locator('#Form_ItemEditForm input[name="LinkPageID"]').inputValue());
}

test('a saved Page link offers the anchors of its page, and the chosen anchor is saved', async ({ page }) => {
    await openRecord(page, 'Page record');
    await expect(page.locator('#Form_ItemEditForm_LinkPageID .treedropdownfield__single-value')).toHaveText('NlB anchor page');
    const anchor = page.locator('#Form_ItemEditForm_LinkPageAnchor');
    await expect(anchor.locator('option')).toHaveText([/Page anchor: \(none\)/, /^\s*intro\s*$/, /^\s*contact\s*$/]);

    await chooseAnchor(page, 'contact');
    await expect(anchor).toHaveValue('contact');
    await save(page);

    await page.reload();
    await expect(linkField(page)).toBeVisible();
    await expect(page.locator('#Form_ItemEditForm_LinkPageAnchor')).toHaveValue('contact');
});

test('the anchor dropdown\'s load URL answers with the page\'s anchors', async ({ page }) => {
    // What the dependent dropdown requests when its page changes: the field's own load action,
    // which calls the module's anchor callback with ?val=<page ID>.
    await openRecord(page, 'Page record');
    const link = await page.locator('#Form_ItemEditForm_LinkPageAnchor').getAttribute('data-link');
    expect(link, 'the anchor dropdown carries its load URL').toBeTruthy();
    const response = await page.request.get(`/${link}?val=${await anchorPageId(page)}`);
    expect(response.status()).toBe(200);
    // Each option as {k: value, v: label} (plus s = selected), in the order they appear in Content.
    const options = (await response.json()) as { k: string; v: string }[];
    expect(options.map(({ k, v }) => ({ k, v }))).toEqual([
        { k: 'intro', v: 'intro' },
        { k: 'contact', v: 'contact' },
    ]);
});

test('admin/namedlinkpageanchors returns the anchors as JSON to a CMS user only', async ({ page, browser, baseURL }) => {
    // A plain Controller, so it reads the LIVE stage: the fixture publishes the anchor page.
    await openRecord(page, 'Page record');
    const pid = await anchorPageId(page);

    const response = await page.request.get(`/admin/namedlinkpageanchors?pid=${pid}`);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/json');
    expect(await response.json()).toEqual({ intro: 'intro', contact: 'contact' });

    // Logged out: CMS_ACCESS_CMSMain is required, so no anchors, only the login form.
    const anonymous = await browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } });
    const denied = await anonymous.request.get(`/admin/namedlinkpageanchors?pid=${pid}`);
    expect(await denied.text()).not.toContain('contact');
    expect(denied.headers()['content-type'] ?? '').not.toContain('application/json');
    await anonymous.close();
});

test('picking a page in the tree loads that page\'s anchors without a save', async ({ page }) => {
    // Was https://github.com/restruct/silverstripe-namedlinkfield/issues/43, fixed in 3.0.4 / 2.1.5.
    // The dependent dropdown listens for a jQuery "change" on input[name=LinkPageID], but it binds
    // that listener to the TreeDropdownField's server-rendered placeholder input, which the React
    // render then replaces, so no load request was made and the anchor list stayed as rendered
    // until the record was saved and reopened.
    await openRecord(page, 'Save record');
    await chooseMode(page, 'Page');
    const loaded = page.waitForRequest((r) => /\/field\/LinkPageAnchor\/load\?/.test(r.url()), { timeout: 5_000 });
    await part(page, 'PageID').locator('.treedropdownfield__control').click();
    await page.locator('#Form_ItemEditForm_LinkPageID .treedropdownfield__option', { hasText: 'NlB anchor page' }).click();
    await loaded;
    await expect(page.locator('#Form_ItemEditForm_LinkPageAnchor option')).toHaveText([/Page anchor: \(none\)/, /^\s*intro\s*$/, /^\s*contact\s*$/]);
});
