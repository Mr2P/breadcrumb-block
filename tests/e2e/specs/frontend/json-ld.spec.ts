import { expect, test } from '@playwright/test';
import { proofShot } from '../../helpers/proof';
import { getStructuredData, getTrail } from '../../helpers/trail';

const urls = [
	'/insprite-of-love/',
	'/docs/installation-walkthrough/',
	'/demo-primary-term/',
	'/category/lifestyle/',
	'/?s=travel',
	'/this-page-does-not-exist-e2e/',
];

test.describe('JSON-LD structured data', () => {
	for (const url of urls) {
		test(`schema matches the visible trail on ${url}`, async ({ page }, testInfo) => {
			await page.goto(url);
			const trail = await getTrail(page);
			const schema = await getStructuredData(page);
			expect(schema, 'BreadcrumbList JSON-LD must be present').not.toBeNull();

			expect(schema!['@context']).toBe('http://schema.org');
			expect(schema!['@type']).toBe('BreadcrumbList');
			expect(schema!.itemListElement).toHaveLength(trail.length);

			schema!.itemListElement.forEach((entry, index) => {
				expect(entry.position).toBe(index + 1);
				expect(entry.item.name).toBe(trail[index].name);
				// Linked items carry the href; the current item carries @id but no visible link.
				if (trail[index].href) {
					expect(entry.item['@id']).toBe(trail[index].href);
				} else {
					expect(entry.item['@id']).toBeTruthy();
				}
			});

			// Exactly one BreadcrumbList script per page.
			await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(1);
			await proofShot(page, testInfo, `jsonld${url.replace(/[^a-z0-9]+/gi, '-')}`);
		});
	}
});