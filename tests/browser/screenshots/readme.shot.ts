import { test, expect, type Page } from '@playwright/test';
import * as path from 'path';

// Generates the README screenshots in docs/images/. Not a test suite: run it with
// playwright.screenshots.config.ts (see README.md next to this file). The content comes from the
// browser fixtures: NlBShowcasePage seeds the "Open day" page with four links (Page + anchor, URL,
// File, Email), the "Visit us" page they point at and a PDF in Files.

const IMAGES = path.join(__dirname, '..', '..', '..', 'docs', 'images');

/**
 * Hide what only the test host has: the fixture ModelAdmin's menu entry ("NamedLinkField browser
 * test") and the specs' anchor page. Presentation only; nothing about the module's own UI changes.
 */
async function hideFixtureChrome(page: Page): Promise<void> {
    await page.addStyleTag({ content: '.cms-menu li:has(> a[href*="admin/nl-browser"]) { display: none !important; }' });
    // CSS cannot match on text, so the specs' page is hidden by its label, in the site tree and in
    // an open page picker alike.
    await page.evaluate(() => {
        document.querySelectorAll<HTMLElement>('.cms-tree li, .treedropdownfield__option').forEach((el) => {
            const label = (el.matches('.cms-tree li') ? el.querySelector(':scope > a') : el)?.textContent?.trim();
            if (label === 'NlB anchor page') {
                el.style.display = 'none';
            }
        });
    });
}

/** Open the showcase page in the page editor and wait until all four link fields are rendered. */
async function openShowcase(page: Page): Promise<void> {
    await page.goto('/admin/pages');
    // Straight to the edit URL the tree links to (page IDs differ per host); a click on the tree
    // node can land before the tree's own script is ready and then does nothing.
    const href = await page.locator('.cms-tree a').filter({ hasText: 'Open day' }).first().getAttribute('href');
    await page.goto('/' + href!.replace(/^\//, ''));
    const form = page.locator('#Form_EditForm');
    for (const name of ['PrimaryButton', 'SecondaryButton', 'Brochure', 'ContactLink']) {
        await expect(form.locator(`[name="${name}Title"]`)).toBeVisible();
    }
    // The saved page and file are shown by title in their tree pickers once React has rendered them.
    await expect(form.locator('#Form_EditForm_PrimaryButton_Holder .LinkFormFieldPageID')).toContainText('Visit us');
    await expect(form.locator('#Form_EditForm_Brochure_Holder .LinkFormFieldFileID')).toContainText('visitor-guide.pdf');
    await expect(form.locator('[name="PrimaryButtonPageAnchor"]')).toHaveValue('directions');
    // The site tree loads separately; hide the specs' page only once it is there.
    await expect(page.locator('.cms-tree')).toContainText('NlB anchor page');
    await hideFixtureChrome(page);
    // No focus ring or hover state in the picture.
    await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
    await page.mouse.move(5, 795);
    await page.waitForTimeout(500);
}

test('Page editor: four links, one per link type', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 1000 });
    await openShowcase(page);

    // From the top of the window to just below the last link field: the Content editor
    // underneath adds nothing.
    const box = (await page.locator('#Form_EditForm_ContactLink_Holder').boundingBox())!;
    await page.screenshot({
        path: path.join(IMAGES, 'page-editor.png'),
        clip: { x: 0, y: 0, width: 1280, height: Math.ceil(box.y + box.height + 16) },
    });
});

test('Page picker: choosing the page to link to', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 1000 });
    await openShowcase(page);

    const holder = page.locator('#Form_EditForm_PrimaryButton_Holder');
    await holder.locator('.LinkFormFieldPageID .treedropdownfield__control').click();
    // The menu first shows only the saved page; the full tree arrives by XHR. Hide the specs' page
    // only once it is there.
    await expect(holder.locator('.treedropdownfield__menu')).toContainText('NlB anchor page');
    await hideFixtureChrome(page);
    await page.waitForTimeout(300);

    // The field and its open menu, with a margin; the menu overlaps the fields below it.
    const field = (await holder.boundingBox())!;
    const menu = (await holder.locator('.treedropdownfield__menu').boundingBox())!;
    const top = Math.max(0, field.y - 16);
    const bottom = Math.max(field.y + field.height, menu.y + menu.height) + 16;
    await page.screenshot({
        path: path.join(IMAGES, 'page-picker.png'),
        clip: { x: field.x - 12, y: top, width: Math.min(1280 - field.x + 12, field.width + 24), height: bottom - top },
    });
});
