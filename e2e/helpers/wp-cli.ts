/**
 * Runs WP-CLI against the test site for fixture seeding/verification.
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { env } from './env';

/**
 * Windows cannot spawn .bat/.cmd binaries directly (EINVAL) and .bat files
 * mangle JSON arguments. Prefer the wp-cli.phar next to wp.bat through php.
 * Returns the command plus any leading arguments.
 */
function resolveWpCommand(): { command: string; prefixArgs: string[] } {
	if (process.platform !== 'win32') {
		return { command: 'wp', prefixArgs: [] };
	}
	try {
		const found = execFileSync('where.exe', ['wp'], { encoding: 'utf8' });
		const bat = found
			.split(/\r?\n/)
			.map((line) => line.trim())
			.find((line) => line.length > 0);
		if (bat) {
			const phar = path.join(path.dirname(bat), 'wp-cli.phar');
			if (fs.existsSync(phar)) {
				return { command: 'php', prefixArgs: [phar] };
			}
			return { command: bat, prefixArgs: [] };
		}
	} catch {
		// Fall through to the error below.
	}
	throw new Error('WP-CLI (wp) not found on PATH. Install wp-cli and make sure `wp` is resolvable.');
}

const wpCommand = resolveWpCommand();

/** Run a wp-cli command against the test site and return its stdout. */
export function wp(args: string[]): string {
	return execFileSync(wpCommand.command, [...wpCommand.prefixArgs, '--path=' + env.sitePath, ...args], {
		encoding: 'utf8',
	});
}

/** WP-CLI with JSON output parsed into a value. */
export function wpJson<T>(args: string[]): T {
	return JSON.parse(wp([...args, '--format=json'])) as T;
}