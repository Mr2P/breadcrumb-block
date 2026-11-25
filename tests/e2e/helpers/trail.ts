/**
 * Helpers to read the rendered breadcrumb trail and its JSON-LD off a page.
 */
import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';

export type TrailItem = {
	/** Rendered label of the item. */
	name: string;
	/** Link URL, or null for the current (last) item. */
	href: string | null;
	/** The full class list of the `li`, e.g. `breadcrumb-item breadcrumb-item--current`. */
	classes: string;
};

export type BreadcrumbListItem = {
	'@type': string;
	position: number;
	item: { name: string; '@id'?: string };
};

export type BreadcrumbListSchema = {
	'@context': string;
	'@type': string;
	itemListElement: BreadcrumbListItem[];
};

/** Read the breadcrumb items from the first `nav.breadcrumb` on the page. */
export async function getTrail(page: Page): Promise<TrailItem[]> {
	const nav = page.locator('nav.breadcrumb').first();
	await expect(nav, 'nav.breadcrumb should be on the page').toBeVisible();
	return nav.locator('li.breadcrumb-item').evaluateAll((items) =>
		items.map((li) => ({
			name: (li.querySelector('.breadcrumb-item-name')?.textContent ?? '').trim(),
			href: li.querySelector('a')?.getAttribute('href') ?? null,
			classes: li.className,
		}))
	);
}

/** Convenience: just the rendered labels of the trail. */
export async function getTrailNames(page: Page): Promise<string[]> {
	const trail = await getTrail(page);
	return trail.map((item) => item.name);
}

/** Assert the trail labels exactly (order included). */
export async function expectTrail(page: Page, expected: string[]): Promise<void> {
	const names = await getTrailNames(page);
	expect(names, 'breadcrumb trail labels').toEqual(expected);
}

/** Parse the BreadcrumbList JSON-LD printed once in the footer. */
export async function getStructuredData(page: Page): Promise<BreadcrumbListSchema | null> {
	const scripts = await page.$$eval('script[type="application/ld+json"]', (nodes) =>
		nodes.map((node) => node.textContent ?? '')
	);
	for (const text of scripts) {
		try {
			const parsed = JSON.parse(text);
			if (parsed && parsed['@type'] === 'BreadcrumbList') {
				return parsed as BreadcrumbListSchema;
			}
		} catch {
			// Not JSON-LD: ignore.
		}
	}
	return null;
}

/** Names of the JSON-LD items, in position order. */
export async function getStructuredDataNames(page: Page): Promise<string[]> {
	const schema = await getStructuredData(page);
	if (!schema) {
		throw new Error('No BreadcrumbList JSON-LD found on the page');
	}
	return schema.itemListElement.map((entry) => entry.item.name);
}