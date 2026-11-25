/**
 * E2E environment loader.
 *
 * Credentials and site overrides are read from `.env.e2e` (git-ignored) so
 * nothing sensitive lands in the repository. Copy `.env.e2e.example` to
 * `.env.e2e` and fill it in.
 */
import fs from 'node:fs';
import path from 'node:path';

// Playwright loads .ts as CommonJS here; resolve from this file's location so
// the loader works regardless of the current working directory. The harness
// lives at tests/e2e/, so the project root is three levels up.
const projectRoot = path.resolve(__dirname, '../../..');
const testsDir = path.resolve(__dirname, '../..');
const envFile = path.join(projectRoot, '.env.e2e');

if (fs.existsSync(envFile)) {
	const lines = fs.readFileSync(envFile, 'utf8').split(/\r?\n/);
	for (const line of lines) {
		const match = /^([A-Z0-9_]+)=(.*)$/.exec(line.trim());
		if (match && process.env[match[1]] === undefined) {
			process.env[match[1]] = match[2];
		}
	}
}

function required(key: string): string {
	const value = process.env[key];
	if (!value) {
		throw new Error(
			`Missing required environment variable "${key}". Create .env.e2e (see .env.e2e.example) and set ${key}.`
		);
	}
	return value;
}

export const env = {
	/** Base URL of the WordPress test site (WAMP vhost). */
	baseUrl: process.env.E2E_BASE_URL || 'https://test.boldblocks.local',
	/** Admin login for the UI specs. */
	adminUser: process.env.E2E_ADMIN_USER || 'bbtest-agent',
	/** Admin password for the UI specs. */
	adminPassword: () => required('E2E_ADMIN_PASSWORD'),
	/** Absolute path of the WordPress install behind the test site (for wp-cli seeding). */
	sitePath: process.env.E2E_SITE_PATH || 'D:/Working/BoldBlocks/Test/Source',
	/** Where Playwright stores the authenticated storage state (git-ignored). */
	storageState: path.join(testsDir, 'e2e-proof', '.auth', 'admin.json'),
	/** Directory for run artifacts: videos, screenshots, gifs (git-ignored). */
	artifactsDir: path.join(testsDir, 'e2e-proof'),
};