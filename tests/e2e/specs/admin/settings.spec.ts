import path from 'node:path';
import { expect, test } from '@playwright/test';
import { proofGif, proofShot, proofTrail } from '../../helpers/proof';
import { wp } from '../../helpers/wp-cli';
import { expectTrail } from '../../helpers/trail';

test.describe('Settings > Breadcrumb Block mapping screen', () => {
	test.beforeEach(async ({ page }) => {
		// Keep the seeded state: docs => doc-section.
		await wp(['option', 'update', 'breadcrumb_block_taxonomies', '{"post":"category","docs":"doc-section"}', '--format=json']);
		await page.goto('/wp-admin/options-general.php?page=breadcrumb-block');
		await expect(page.locator('h1')).toHaveText('Breadcrumb Block');
	});

	test('renders a dropdown per post type with the current mapping', async ({ page }, testInfo) => {
		await expect(page.locator('select#breadcrumb_block_taxonomies-post')).toBeVisible();
		await expect(page.locator('select#breadcrumb_block_taxonomies-post')).toHaveValue('category');

		// doc-topic is a flat public taxonomy: it must be offered too.
		const docsSelect = page.locator('select#breadcrumb_block_taxonomies-docs');
		await expect(docsSelect).toHaveValue('doc-section');
		await expect(docsSelect.locator('option')).toHaveText(['Default', 'Doc Sections', 'Doc Topics']);
		await proofShot(page, testInfo, 'settings-screen');
	});

	test('saving a flat mapping changes the trail', async ({ page }, testInfo) => {
		// Assign a flat doc-topic to a doc so the changed mapping is visible.
		wp(['post', 'term', 'set', '1549', 'doc-topic', 'beginner']);

		await page.selectOption('select#breadcrumb_block_taxonomies-docs', 'doc-topic');
		// GIF proof of the save action (notice appears, mapping is stored).
		const gifFrames = proofGif(page, testInfo, 'settings-save', { frames: 10, intervalMs: 300 });
		await page.click('#submit');
		await expect(page.locator('.updated, .notice-success')).toBeVisible();
		await page.waitForTimeout(600);
		await page.screenshot({ path: path.join(path.dirname(await gifFrames), 'settings-saved.png') });
		await gifFrames;

		await page.goto('/docs/api-reference/');
		await expectTrail(page, ['Home', 'Beginner', 'API Reference']);
		await proofTrail(page, testInfo, 'settings-flat-mapping');

		// Revert and verify the default mapping returns.
		await page.goto('/wp-admin/options-general.php?page=breadcrumb-block');
		await page.selectOption('select#breadcrumb_block_taxonomies-docs', 'doc-section');
		await page.click('#submit');
		await page.goto('/docs/api-reference/');
		await expectTrail(page, ['Home', 'Reference', 'API Reference']);
	});

	test.afterEach(async () => {
		// Restore the seeded mapping and doc-topic assignment state.
		wp(['post', 'term', 'set', '1549', 'doc-topic', '']);
		wp(['option', 'update', 'breadcrumb_block_taxonomies', '{"post":"category","docs":"doc-section"}', '--format=json']);
	});
});