import { expect, test } from '@playwright/test';
import { proofGif, proofShot } from '../../helpers/proof';
import { wp } from '../../helpers/wp-cli';
import { openBreadcrumbPanel, openPostEditor, waitForPrimaryTermValue } from '../../helpers/editor';

test.describe('Editor sidebar Breadcrumb panel', () => {
	test.beforeEach(async ({ page }) => {
		// Seed the known fixture so hydration assertions are deterministic.
		wp(['post', 'meta', 'update', '1550', '_breadcrumb_primary_term', '55']);
		wp(['post', 'meta', 'update', '1550', '_breadcrumb_short_title', 'Big Release']);
		await openPostEditor(page, 1550);
	});

	test('shows the saved short title and primary term', async ({ page }, testInfo) => {
		const { shortTitle, primaryTerm } = await openBreadcrumbPanel(page);

		await waitForPrimaryTermValue(primaryTerm, '55'); // Tech, not the first category News.
		await expect(shortTitle).toHaveValue('Big Release');
		await expect(primaryTerm).toHaveValue('55');
		// The picker lists the terms assigned to the post.
		await expect(primaryTerm.locator('option')).toHaveText(['Default', 'News', 'Tech']);
		await expect(primaryTerm.locator('option:checked')).toHaveText('Tech');
		await proofGif(page, testInfo, 'editor-panel', { frames: 6, intervalMs: 400 });
		await proofShot(page, testInfo, 'editor-panel');
	});

	test('edit is local until saved', async ({ page }) => {
		const { shortTitle } = await openBreadcrumbPanel(page);

		await shortTitle.fill('E2E Draft');
		await expect(shortTitle).toHaveValue('E2E Draft');

		// Reload without saving: the stored meta wins.
		await openPostEditor(page, 1550);
		const panel = await openBreadcrumbPanel(page);
		await expect(panel.shortTitle).toHaveValue('Big Release');
	});

	test('panel is present on docs CPT too', async ({ page }) => {
		await openPostEditor(page, 1549);
		const panel = await openBreadcrumbPanel(page);

		// API Reference has no short title and no primary term meta.
		await expect(panel.shortTitle).toHaveValue('');
		await expect(panel.primaryTerm).toHaveValue('0');
	});
});