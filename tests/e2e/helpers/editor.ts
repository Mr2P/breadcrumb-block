/**
 * Helpers for driving the block editor in wp-admin (WP 7.2 renders the canvas
 * in an iframe; the sidebar panels live in the top document). The editor boots
 * asynchronously and occasionally opens a stray dialog, hence the settling
 * loop.
 */
import type { Locator, Page } from '@playwright/test';
import { expect } from '@playwright/test';

/** Open a post in the editor and wait until it is interactive. */
export async function openPostEditor(page: Page, postId: number): Promise<void> {
	const url = `/wp-admin/post.php?post=${postId}&action=edit`;
	for (let attempt = 0; attempt < 3; attempt++) {
		await page.goto(url, { waitUntil: 'load' });
		const canvas = page.frameLocator('iframe[name="editor-canvas"]').locator('.block-editor-block-list__layout');
		await canvas.waitFor({ timeout: 30_000 });
		await dismissPostLock(page);
		await openSettingsSidebar(page);
		return;
	}
}

/**
 * The sandbox is shared: another user (or a previous run) can hold the post
 * lock, which shows a takeover dialog and hides the editor behind it. Taking
 * over keeps the specs deterministic.
 */
async function dismissPostLock(page: Page): Promise<void> {
	const dialog = page
		.locator('[role="dialog"], .components-modal__content')
		.filter({ hasText: /being edited/i });
	if (await dialog.count()) {
		await dialog.getByRole('button', { name: /Take over/i }).click();
		await page.waitForTimeout(1200);
	}
}

/** Dismiss any stray overlay dialog (e.g. the link dialog) and open the sidebar. */
async function openSettingsSidebar(page: Page): Promise<void> {
	for (let i = 0; i < 30; i++) {
		const modalOpen = await page.evaluate(() => document.body.className.includes('modal-open'));
		if (modalOpen) {
			await page.keyboard.press('Escape').catch(() => {});
			await page.waitForTimeout(500);
			if (await page.evaluate(() => document.body.className.includes('modal-open'))) {
				const closeBtn = page.locator('[role="dialog"] button[aria-label="Close"]').first();
				if (await closeBtn.count()) {
					await closeBtn.click({ force: true }).catch(() => {});
				}
			}
			continue;
		}
		const settings = page.getByRole('button', { name: 'Settings', exact: true });
		try {
			await settings.waitFor({ state: 'visible', timeout: 2000 });
			if ((await settings.getAttribute('aria-pressed')) !== 'true') {
				await settings.click();
			}
			return;
		} catch {
			// Keep polling: the editor is still booting.
		}
		await page.waitForTimeout(1000);
	}
	throw new Error('Editor never became interactive');
}

/** Expand the Breadcrumb panel and wait for its fields. */
export async function openBreadcrumbPanel(page: Page): Promise<{ shortTitle: Locator; primaryTerm: Locator }> {
	const panelToggle = page.getByRole('button', { name: 'Breadcrumb', exact: true });
	await expect(panelToggle).toBeVisible({ timeout: 30_000 });
	if ((await panelToggle.getAttribute('aria-expanded')) === 'false') {
		await panelToggle.click();
	}
	const shortTitle = page.getByLabel('Breadcrumb short title');
	const primaryTerm = page.getByLabel('Primary term');
	await expect(shortTitle).toBeVisible({ timeout: 30_000 });
	await expect(primaryTerm).toBeVisible({ timeout: 30_000 });
	return { shortTitle, primaryTerm };
}

/** Wait until the Primary term select reflects the stored value (hydration). */
export async function waitForPrimaryTermValue(primaryTerm: Locator, expected: string): Promise<void> {
	for (let i = 0; i < 60; i++) {
		if ((await primaryTerm.inputValue()) === expected) {
			return;
		}
		await primaryTerm.page().waitForTimeout(500);
	}
	throw new Error(`Primary term never hydrated to ${expected} (saw ${await primaryTerm.inputValue()})`);
}