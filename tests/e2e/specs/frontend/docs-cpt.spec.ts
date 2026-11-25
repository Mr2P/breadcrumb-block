import { expect, test } from '@playwright/test';
import { proofGif, proofTrail } from '../../helpers/proof';
import { expectTrail, getTrail } from '../../helpers/trail';

test.describe('Docs custom post type (site-level taxonomy mapping)', () => {
	test('doc-section term: Home / Term / Title', async ({ page }, testInfo) => {
		await page.goto('/docs/getting-started/');
		await expectTrail(page, ['Home', 'Guides', 'Getting Started']);
		const trail = await getTrail(page);
		expect(trail[1].href).toBe('https://test.boldblocks.local/doc-sections/guides/');
		expect(trail[2].href).toBeNull();
		await proofTrail(page, testInfo, 'docs-single');
	});

	test('ancestors are walked: Home / Guides / Installation / Title', async ({ page }, testInfo) => {
		await page.goto('/docs/installation-walkthrough/');
		await expectTrail(page, ['Home', 'Guides', 'Installation', 'Installation Walkthrough']);
		await proofTrail(page, testInfo, 'docs-ancestors');
		await proofGif(page, testInfo, 'docs-ancestors');
	});

	test('second top-level section', async ({ page }, testInfo) => {
		await page.goto('/docs/api-reference/');
		await expectTrail(page, ['Home', 'Reference', 'API Reference']);
		await proofTrail(page, testInfo, 'docs-reference');
	});

	test('docs post type archive', async ({ page }, testInfo) => {
		await page.goto('/docs/');
		await expectTrail(page, ['Home', 'Docs']);
		await proofTrail(page, testInfo, 'docs-archive');
	});
});