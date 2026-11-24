import { expect, test } from '@playwright/test';
import { proofGif, proofShot } from '../../helpers/proof';

/**
 * WP 7.2 renders the canvas in an iframe; the sidebar panels live in the top
 * document. The editor boots asynchronously, so every test waits for the
 * canvas before touching the sidebar.
 */
async function openBreadcrumbPanel(page: import('@playwright/test').Page) {
	const panelToggle = page.getByRole('button', { name: 'Breadcrumb', exact: true });
	await expect(panelToggle).toBeVisible({ timeout: 30_000 });
	if ((await panelToggle.getAttribute('aria-expanded')) === 'false') {
		await panelToggle.click();
	}
	await expect(page.getByLabel('Breadcrumb short title')).toBeVisible({ timeout: 30_000 });
}

test.describe('Editor sidebar Breadcrumb panel', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/post.php?post=1550&action=edit');
		// Wait for the editor canvas inside its iframe (boot is async).
		await page
			.frameLocator('iframe[name="editor-canvas"]')
			.locator('.block-editor-block-list__layout')
			.waitFor({ timeout: 30_000 });
	});

	test('shows the saved short title and primary term', async ({ page }, testInfo) => {
		await openBreadcrumbPanel(page);

		await expect(page.getByLabel('Breadcrumb short title')).toHaveValue('Big Release');

		const primaryTerm = page.getByLabel('Primary term');
		await expect(primaryTerm).toHaveValue('55'); // Tech, not the first category News.
		// The picker lists the terms assigned to the post (meta 55 = Tech).
		await expect(primaryTerm.locator('option')).toHaveText(['Default', 'News', 'Tech']);
		await expect(primaryTerm.locator('option:checked')).toHaveText('Tech');
		// GIF proof: hover over the dropdown to show the selected primary term.
		await proofGif(page, testInfo, 'editor-panel', { frames: 6, intervalMs: 400 });
		await proofShot(page, testInfo, 'editor-panel');
	});

	test('edit is local until saved', async ({ page }) => {
		await openBreadcrumbPanel(page);

		const shortTitle = page.getByLabel('Breadcrumb short title');
		await shortTitle.fill('E2E Draft');
		await expect(shortTitle).toHaveValue('E2E Draft');

		// Reload without saving: the stored meta wins.
		await page.reload();
		await page
			.frameLocator('iframe[name="editor-canvas"]')
			.locator('.block-editor-block-list__layout')
			.waitFor({ timeout: 30_000 });
		await openBreadcrumbPanel(page);
		await expect(page.getByLabel('Breadcrumb short title')).toHaveValue('Big Release');
	});

	test('panel is present on docs CPT too', async ({ page }) => {
		await page.goto('/wp-admin/post.php?post=1549&action=edit');
		await page
			.frameLocator('iframe[name="editor-canvas"]')
			.locator('.block-editor-block-list__layout')
			.waitFor({ timeout: 30_000 });
		await openBreadcrumbPanel(page);

		// API Reference has no short title and no primary term meta.
		await expect(page.getByLabel('Breadcrumb short title')).toHaveValue('');
		await expect(page.getByLabel('Primary term')).toHaveValue('0');
	});
});