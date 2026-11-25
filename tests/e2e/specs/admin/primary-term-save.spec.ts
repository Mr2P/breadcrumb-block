import { expect, test } from '@playwright/test';
import { proofGif, proofTrail } from '../../helpers/proof';
import { wp } from '../../helpers/wp-cli';
import { expectTrail } from '../../helpers/trail';
import { openBreadcrumbPanel, openPostEditor, waitForPrimaryTermValue } from '../../helpers/editor';

test.describe('Primary term save (Custom Fields metabox regression)', () => {
	// The Custom Fields metabox is off by default. This regression covers the
	// environment where it is ON (enable_custom_fields user meta): the metabox
	// must not list the plugin meta keys, or its stale page-load value would
	// overwrite the sidebar panel save.
	test.beforeEach(async () => {
		wp(['user', 'meta', 'update', '4', 'enable_custom_fields', '1']);
		// Seeded fixture: primary term Tech (55), short title "Big Release".
		wp(['post', 'meta', 'update', '1550', '_breadcrumb_primary_term', '55']);
		wp(['post', 'meta', 'update', '1550', '_breadcrumb_short_title', 'Big Release']);
	});

	test('Custom Fields metabox does not expose managed meta keys', async ({ page }, testInfo) => {
		await openPostEditor(page, 1550);
		const customFields = page.locator('#postcustom').first();
		await expect(customFields).toBeAttached({ timeout: 30_000 });
		const text = (await customFields.textContent()) || '';
		expect(text).not.toMatch(/breadcrumb/i);
		await proofGif(page, testInfo, 'custom-fields-hidden', { frames: 4, intervalMs: 400 });
	});

	test('primary term chosen in the sidebar persists through save', async ({ page }, testInfo) => {
		await openPostEditor(page, 1550);
		const { primaryTerm } = await openBreadcrumbPanel(page);
		await waitForPrimaryTermValue(primaryTerm, '55'); // Tech, while News is the first category.
		await primaryTerm.selectOption('54'); // News
		await page.getByRole('button', { name: 'Save', exact: true }).click();
		await page.waitForTimeout(6000);

		// Reload: the stored meta wins over any stale classic metabox value.
		await openPostEditor(page, 1550);
		const panel = await openBreadcrumbPanel(page);
		await waitForPrimaryTermValue(panel.primaryTerm, '54');
		await expect(page.getByLabel('Breadcrumb short title')).toHaveValue('Big Release');
		await proofGif(page, testInfo, 'primary-term-saved', { frames: 4, intervalMs: 400 });

		// The front-end trail leads through the saved term.
		await page.goto('/demo-primary-term/');
		await expectTrail(page, ['Home', 'News', 'Big Release']);
		await proofTrail(page, testInfo, 'primary-term-saved');
	});

	test.afterEach(async () => {
		// Restore the seeded fixture.
		wp(['post', 'meta', 'update', '1550', '_breadcrumb_primary_term', '55']);
		wp(['post', 'meta', 'update', '1550', '_breadcrumb_short_title', 'Big Release']);
	});
});