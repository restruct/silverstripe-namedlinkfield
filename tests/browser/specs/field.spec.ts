import {
    test,
    expect,
    MODES,
    chooseMode,
    expectVisibleFor,
    linkField,
    openRecord,
    part,
    save,
} from './support';

// The NamedLinkFormField in a CMS edit form: it renders with the module's assets, shows only the
// inputs of the selected link type (client/src/js/legacy/LinkFormField.entwine.js), and saves each
// part of the link into the composite NamedLinkField columns.

test.describe('Rendering', () => {
    test('the field renders with its parts and the module bundle and stylesheet load', async ({ page }) => {
        // LeftAndMain extra_requirements (_config/config.yml) add both files to every admin page;
        // watch the full page load of the list for them.
        const assets = new Map<string, number>();
        page.on('response', (r) => {
            const m = r.url().match(/namedlinkfield\/client\/dist\/(js\/bundle\.js|css\/namedlinkfield\.css)/);
            if (m) assets.set(m[1], r.status());
        });
        await openRecord(page, 'URL record');
        expect(assets.get('js/bundle.js'), 'client/dist/js/bundle.js loaded').toBe(200);
        expect(assets.get('css/namedlinkfield.css'), 'client/dist/css/namedlinkfield.css loaded').toBe(200);

        // Scaffolded from the $db field: one holder titled after the field, Title + Type first.
        const holder = page.locator('#Form_ItemEditForm_Link_Holder');
        await expect(holder.locator('> label')).toHaveText('Link');
        await expect(page.locator('#Form_ItemEditForm_LinkTitle')).toHaveValue('Example');
        const modeSelect = page.locator('#Form_ItemEditForm_LinkLinkmode');
        await expect(modeSelect).toHaveValue('URL');
        await expect(modeSelect.locator('option')).toHaveText([...MODES].map((m) => new RegExp(`^\\s*${m}\\s*$`)));
        await expect(page.locator('#Form_ItemEditForm_LinkCustomURL')).toHaveValue('https://example.com/');

        // The stylesheet applied: rules only namedlinkfield.css sets (under .namedlinkform, the
        // holder class) put Title and Type side by side and space the two rows.
        await expect(linkField(page).locator('.namedlink-row')).toHaveCount(2);
        await expect(linkField(page).locator('.namedlink-row').first()).toHaveCSS('margin-bottom', '6px');
        await expect(linkField(page).locator('.LinkFormFieldTitle')).toHaveCSS('display', 'inline-block');
    });

    const seeded = [
        { title: 'URL record', mode: 'URL' as const },
        { title: 'Email record', mode: 'Email' as const },
        { title: 'Page record', mode: 'Page' as const },
        { title: 'Shortcode record', mode: 'Shortcode' as const },
    ];
    for (const { title, mode } of seeded) {
        test(`a saved ${mode} link opens with only the ${mode} inputs shown`, async ({ page }) => {
            await openRecord(page, title);
            await expect(page.locator('#Form_ItemEditForm_LinkLinkmode')).toHaveValue(mode);
            await expectVisibleFor(page, mode);
        });
    }
});

test.describe('Switching the link type', () => {
    test('each link type shows its own inputs and hides the others', async ({ page }) => {
        await openRecord(page, 'Switch record');
        await expectVisibleFor(page, 'URL');
        // Every type once, ending on a different one than it started with, so each transition
        // is a real change event.
        for (const mode of ['Page', 'File', 'Email', 'Shortcode', 'URL', 'Page'] as const) {
            await chooseMode(page, mode);
            await expectVisibleFor(page, mode);
        }
    });
});

test.describe('Saving', () => {
    test('title, type and URL are saved and shown again after a reload', async ({ page }) => {
        await openRecord(page, 'Save record');
        const stamp = `${Date.now()}`;
        await page.locator('#Form_ItemEditForm_LinkTitle').fill(`Saved ${stamp}`);
        await chooseMode(page, 'URL');
        await part(page, 'CustomURL').locator('input').fill(`https://example.net/${stamp}`);
        await save(page);

        await page.reload();
        await expect(linkField(page)).toBeVisible();
        await expect(page.locator('#Form_ItemEditForm_LinkTitle')).toHaveValue(`Saved ${stamp}`);
        await expect(page.locator('#Form_ItemEditForm_LinkLinkmode')).toHaveValue('URL');
        await expect(page.locator('#Form_ItemEditForm_LinkCustomURL')).toHaveValue(`https://example.net/${stamp}`);
        await expectVisibleFor(page, 'URL');
    });
});
