import { expect, test } from '@playwright/test';
import { proofGif, proofTrail } from '../../helpers/proof';
import { expectTrail, getStructuredDataNames, getTrail } from '../../helpers/trail';

test.describe('Primary term meta', () => {
	test('primary term overrides the first category', async ({ page }, testInfo) => {
		// demo-primary-term is in News + Tech, with Tech stored as the primary term.
		await page.goto('/demo-primary-term/');
		await expectTrail(page, ['Home', 'Tech', 'Big Release']);
		const trail = await getTrail(page);
		expect(trail[1].href).toBe('https://test.boldblocks.local/category/tech/');
		// The visible trail and the JSON-LD agree.
		expect(await getStructuredDataNames(page)).toEqual(['Home', 'Tech', 'Big Release']);
		await proofTrail(page, testInfo, 'primary-term-tech');
		await proofGif(page, testInfo, 'primary-term-tech');
	});

	test('no primary term falls back to the default term', async ({ page }, testInfo) => {
		// demo-short-title has the first (News) category, primary meta points to News anyway.
		await page.goto('/demo-short-title/');
		await expectTrail(page, ['Home', 'News', 'Scaling Story']);
		await proofTrail(page, testInfo, 'primary-term-default');
	});
});