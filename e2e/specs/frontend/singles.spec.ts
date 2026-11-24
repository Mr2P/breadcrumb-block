import { expect, test } from '@playwright/test';
import { proofGif, proofShot, proofTrail } from '../../helpers/proof';
import { getTrail, expectTrail } from '../../helpers/trail';

test.describe('Single items', () => {
	test('post with one category: Home / Category / Post title', async ({ page }, testInfo) => {
		await page.goto('/insprite-of-love/');
		await expectTrail(page, ['Home', 'Lifestyle', 'Insprite of love']);
		const trail = await getTrail(page);
		expect(trail[0].classes).toContain('breadcrumb-item--home');
		expect(trail[0].href).toBe('https://test.boldblocks.local/');
		expect(trail[1].href).toBe('https://test.boldblocks.local/category/lifestyle/');
		// The current item has no link and is marked aria-current.
		expect(trail[2].href).toBeNull();
		expect(trail[2].classes).toContain('breadcrumb-item--current');
		await expect(page.locator('nav.breadcrumb').first().locator('li.breadcrumb-item--current')).toHaveAttribute(
			'aria-current',
			'page'
		);
		expect(page.locator('nav.breadcrumb').first()).toHaveAttribute('aria-label', 'Breadcrumbs');
		await proofShot(page, testInfo, 'post-single');
		await proofTrail(page, testInfo, 'post-single');
		await proofGif(page, testInfo, 'post-single');
	});

	test('page trail: Home / Page title, no term path', async ({ page }, testInfo) => {
		await page.goto('/bb-agent-test/');
		await expectTrail(page, ['Home', 'Breadcrumb Agent Test']);
		const trail = await getTrail(page);
		expect(trail[1].href).toBeNull();
		await proofTrail(page, testInfo, 'page');
	});

	test('custom home label and svg separator', async ({ page }, testInfo) => {
		await page.goto('/breadcrumb-block/');
		await expectTrail(page, ['Start here', 'Test plugins', 'Breadcrumb Block']);
		const sep = page.locator('nav.breadcrumb').first().locator('span.sep').first();
		await expect(sep.locator('svg')).toHaveCount(1);
		await proofTrail(page, testInfo, 'custom-separator');
		await proofGif(page, testInfo, 'custom-separator');
	});
});