import { defineConfig, devices } from '@playwright/test';
import { env } from './e2e/helpers/env';

/**
 * E2E harness for the Breadcrumb Block plugin.
 *
 * Runs against the local WAMP test site (https://test.boldblocks.local,
 * plugin symlinked from this repo). This config lives in tests/ next to the
 * e2e/ specs; paths below are relative to it. Proof artifacts - step
 * screenshots and GIFs - land in the git-ignored tests/e2e-proof/ directory.
 */
export default defineConfig({
	testDir: './e2e',
	outputDir: 'e2e-proof/artifacts',
	fullyParallel: false,
	workers: 1,
	forbidOnly: !!process.env.CI,
	retries: 0,
	timeout: 60_000,
	expect: { timeout: 10_000 },
	reporter: [
		['list'],
		['html', { outputFolder: 'playwright-report', open: 'never' }],
	],
	use: {
		baseURL: env.baseUrl,
		ignoreHTTPSErrors: true,
		viewport: { width: 1280, height: 720 },
		screenshot: 'on',
		// GIF proofs come from proofGif() frame capture + make-gifs.mjs; the
		// bundled Playwright ffmpeg cannot encode GIFs out of the webm videos.
		video: 'off',
		trace: 'retain-on-failure',
	},
	projects: [
		{
			name: 'setup',
			testMatch: 'auth.setup.ts',
		},
		{
			name: 'frontend',
			testMatch: 'specs/frontend/*.spec.ts',
			use: { ...devices['Desktop Chrome'] },
			dependencies: [],
		},
		{
			name: 'admin',
			testMatch: 'specs/admin/*.spec.ts',
			use: {
				...devices['Desktop Chrome'],
				storageState: env.storageState,
			},
			// Admin flows boot the block editor more than once per test; the
			// shared sandbox can be slow (other sessions, cold WAMP cache).
			timeout: 120_000,
			dependencies: ['setup'],
		},
	],
});