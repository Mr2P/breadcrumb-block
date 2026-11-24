import { expect, test } from '@playwright/test';
import { wp, wpJson } from '../../helpers/wp-cli';

/**
 * Preflight: verifies the sandbox fixtures the other specs rely on. If the
 * test site drifted (re-imported demo content, reset DB), this fails fast
 * with a precise reason instead of confusion inside render assertions.
 */

test.describe('Sandbox fixtures', () => {
	test('plugin is active', () => {
		expect(wp(['plugin', 'list', '--format=csv'])).toContain('breadcrumb-block,active');
	});

	test('site-level taxonomy mapping is seeded', () => {
		const mapping = wpJson<Record<string, string>>(['option', 'get', 'breadcrumb_block_taxonomies']);
		expect(mapping).toMatchObject({ post: 'category', docs: 'doc-section' });
	});

	test('demo posts carry short title and primary term meta', () => {
		const meta = wpJson<Array<{ meta_key: string; meta_value: string }>>(['post', 'meta', 'list', '1550']);
		const get = (key: string) => meta.find((m) => m.meta_key === key)?.meta_value;
		expect(get('breadcrumb_short_title')).toBe('Big Release');
		expect(get('breadcrumb_primary_term')).toBe('55'); // Tech, while News is the first category.
	});

	test('docs posts are assigned to doc sections', () => {
		const terms = wp(['post', 'term', 'list', '1547', 'doc-section', '--format=json']);
		expect(terms).toContain('guides');
	});
});