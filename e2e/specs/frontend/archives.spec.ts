import { expect, test } from '@playwright/test';
import { proofGif, proofTrail } from '../../helpers/proof';
import { expectTrail } from '../../helpers/trail';

test.describe('Archive, search and 404 contexts', () => {
	test('category archive', async ({ page }, testInfo) => {
		await page.goto('/category/lifestyle/');
		await expectTrail(page, ['Home', 'Lifestyle']);
		await proofTrail(page, testInfo, 'category');
		await proofGif(page, testInfo, 'category');
	});

	test('child category archive', async ({ page }, testInfo) => {
		await page.goto('/category/news/sub-news/');
		await expectTrail(page, ['Home', 'News', 'Sub News']);
		await proofTrail(page, testInfo, 'child-category');
	});

	test('tag archive', async ({ page }, testInfo) => {
		await page.goto('/tag/traveling/');
		await expectTrail(page, ['Home', 'Posts tagged \u201ctraveling\u201d']);
		await proofTrail(page, testInfo, 'tag');
		await proofGif(page, testInfo, 'tag');
	});

	test('author archive', async ({ page }, testInfo) => {
		await page.goto('/author/author/');
		await expectTrail(page, ['Home', 'Author: author']);
		await proofTrail(page, testInfo, 'author');
	});

	test('year archive', async ({ page }, testInfo) => {
		await page.goto('/2026/');
		await expectTrail(page, ['Home', '2026']);
		await proofTrail(page, testInfo, 'year');
	});

	test('month archive', async ({ page }, testInfo) => {
		await page.goto('/2026/03/');
		await expectTrail(page, ['Home', '2026', 'March']);
		await proofTrail(page, testInfo, 'month');
	});

	test('day archive', async ({ page }, testInfo) => {
		await page.goto('/2026/03/28/');
		await expectTrail(page, ['Home', '2026', 'March', '28']);
		await proofTrail(page, testInfo, 'day');
	});

	test('search results', async ({ page }, testInfo) => {
		await page.goto('/?s=travel');
		await expectTrail(page, ['Home', 'Search results for \u201ctravel\u201d']);
		await proofTrail(page, testInfo, 'search');
		await proofGif(page, testInfo, 'search');
	});

	test('404', async ({ page }, testInfo) => {
		await page.goto('/this-page-does-not-exist-e2e/');
		await expectTrail(page, ['Home', 'Page not found']);
		const current = page.locator('nav.breadcrumb').first().locator('li.breadcrumb-item').last();
		await expect(current).toHaveClass(/breadcrumb-item--current/);
		await proofTrail(page, testInfo, '404');
		await proofGif(page, testInfo, '404');
	});

	test('paged blog home: Home / site title / Page %d', async ({ page }, testInfo) => {
		// Regression: single_post_title() is empty before the loop; the home
		// crumb used to fatal with a null name.
		await page.goto('/page/2/');
		await expectTrail(page, ['Home', 'BoldBlocks', 'Page 2']);
		await proofTrail(page, testInfo, 'paged');
		await proofGif(page, testInfo, 'paged');
	});
});