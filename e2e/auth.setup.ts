/**
 * Logs into wp-admin once per run and stores the authenticated browser state
 * so the admin specs can skip the login form. State lives in e2e-proof/ (git-ignored).
 */
import fs from 'node:fs';
import path from 'node:path';
import { test as setup, expect } from '@playwright/test';
import { env } from './helpers/env';

setup('authenticate as admin', async ({ page }) => {
	fs.mkdirSync(path.dirname(env.storageState), { recursive: true });
	const password = env.adminPassword();

	await page.goto('/wp-login.php');
	await page.fill('#user_login', env.adminUser);
	await page.fill('#user_pass', password);
	await page.click('#wp-submit');

	await page.waitForURL(/wp-(admin|login\.php)/, { timeout: 30_000 });
	await expect(page.locator('#wpadminbar, body.profile-php, #loginform')).toBeVisible();

	if (page.url().includes('wp-login.php')) {
		throw new Error(
			`Admin login failed for "${env.adminUser}". Check E2E_ADMIN_PASSWORD in .env.e2e and that the user exists on the test site.`
		);
	}

	await page.context().storageState({ path: env.storageState });
});