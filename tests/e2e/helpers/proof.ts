/**
 * Proof helpers: every spec captures a labelled screenshot and a frame
 * sequence at the key moment of the flow. `node tests/e2e/make-gifs.mjs` encodes
 * the frame sequences into GIFs after a run. All artifacts land under the
 * git-ignored tests/e2e-proof/ directory.
 */
import fs from 'node:fs';
import path from 'node:path';
import type { Page, TestInfo } from '@playwright/test';

/** Slug for a test title, stable enough to be reused as a file name. */
export function slugify(value: string): string {
	return value
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '')
		.slice(0, 80);
}

/** Capture a labelled screenshot into this test's artifact directory. */
export async function proofShot(page: Page, testInfo: TestInfo, label: string): Promise<string> {
	const file = path.join(testInfo.outputDir, `proof-${slugify(label)}.png`);
	await page.screenshot({ path: file, fullPage: false });
	return file;
}

/** Capture the trail region only - the tightest proof of what rendered. */
export async function proofTrail(page: Page, testInfo: TestInfo, label: string): Promise<string> {
	const file = path.join(testInfo.outputDir, `trail-${slugify(label)}.png`);
	await page.locator('nav.breadcrumb').first().screenshot({ path: file });
	return file;
}

/**
 * Capture a frame sequence of the live flow; make-gifs.mjs encodes it into a
 * GIF proof. Uses screenshots, so it works without a system ffmpeg.
 */
export async function proofGif(
	page: Page,
	testInfo: TestInfo,
	label: string,
	options: { frames?: number; intervalMs?: number } = {}
): Promise<string> {
	const { frames = 12, intervalMs = 250 } = options;
	const dir = path.join(testInfo.outputDir, `gif-frames-${slugify(label)}`);
	fs.mkdirSync(dir, { recursive: true });

	for (let frame = 0; frame < frames; frame++) {
		const file = path.join(dir, `frame-${String(frame).padStart(3, '0')}.png`);
		await page.screenshot({ path: file });
		if (frame < frames - 1) {
			await page.waitForTimeout(intervalMs);
		}
	}
	return dir;
}