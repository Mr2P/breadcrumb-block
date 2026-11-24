import { expect, test } from '@playwright/test';
import { proofGif, proofTrail } from '../../helpers/proof';
import { expectTrail, getStructuredDataNames, getTrailNames } from '../../helpers/trail';

test.describe('Short title meta', () => {
	test('short title replaces the full title in trail and JSON-LD', async ({ page }, testInfo) => {
		await page.goto('/demo-short-title/');
		await expectTrail(page, ['Home', 'News', 'Scaling Story']);
		// The real title must not appear anywhere in the trail markup.
		const html = await page.locator('nav.breadcrumb').first().innerHTML();
		expect(html).not.toContain('How We Scaled Our Platform to Serve a Million Users Without Downtime');
		expect(await getStructuredDataNames(page)).toEqual(['Home', 'News', 'Scaling Story']);
		await proofTrail(page, testInfo, 'short-title-post');
		await proofGif(page, testInfo, 'short-title-post');
	});

	test('works for pages and for the term path', async ({ page }, testInfo) => {
		await page.goto('/long-title-demo/');
		await expectTrail(page, ['Home', 'Uncategorized', 'Multisite Settings']);
		expect(await getTrailNames(page)).not.toContain(
			'The Complete Guide to Configuring Advanced Multisite Network Settings for Large Organizations'
		);
		await proofTrail(page, testInfo, 'short-title-long');
	});

	test('missing meta keeps the real title', async ({ page }) => {
		await page.goto('/insprite-of-love/');
		await expectTrail(page, ['Home', 'Lifestyle', 'Insprite of love']);
	});
});