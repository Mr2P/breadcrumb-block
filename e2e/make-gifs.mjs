#!/usr/bin/env node
/**
 * Encodes the per-test frame sequences captured by proofGif()
 * (e2e-proof/artifacts/.../gif-frames-.../ directories) into GIFs in
 * e2e-proof/gifs, using pure JS (gifenc + pngjs) - no system ffmpeg needed.
 * Also writes a manifest of every proof artifact.
 *
 * Run after `playwright test` (or use `yarn test:e2e`, which does both).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import gifenc from 'gifenc';
import { PNG } from 'pngjs';

const { GIFEncoder, quantize, applyPalette } = gifenc;

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const artifactsDir = path.join(projectRoot, 'e2e-proof', 'artifacts');
const gifsDir = path.join(projectRoot, 'e2e-proof', 'gifs');

function walk(dir) {
	const found = [];
	if (!fs.existsSync(dir)) {
		return found;
	}
	for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
		const full = path.join(dir, entry.name);
		if (entry.isDirectory()) {
			found.push(...walk(full));
		} else {
			found.push(full);
		}
	}
	return found;
}

/** Decode a PNG file into the RGBA buffers gifenc expects. */
function readFrame(file) {
	const png = PNG.sync.read(fs.readFileSync(file));
	return [png.data, png.width, png.height];
}

/** Encode one frame sequence into a looping GIF. */
function framesToGif(frameFiles, gifFile, { width = 640, fps = 4 } = {}) {
	const scale = (img) => {
		// Nearest-neighbour downscale to keep the GIF small.
		if (img[1] <= width) {
			return img;
		}
		const rgba = img[0];
		const fromW = img[1];
		const fromH = img[2];
		const toH = Math.round((fromH * width) / fromW);
		const out = Buffer.alloc(width * toH * 4);
		for (let y = 0; y < toH; y++) {
			const sy = Math.floor((y * fromH) / toH);
			for (let x = 0; x < width; x++) {
				const sx = Math.floor((x * fromW) / width);
				const from = (sy * fromW + sx) * 4;
				const to = (y * width + x) * 4;
				out[to] = rgba[from];
				out[to + 1] = rgba[from + 1];
				out[to + 2] = rgba[from + 2];
				out[to + 3] = rgba[from + 3];
			}
		}
		return [out, width, toH];
	};

	const gif = GIFEncoder();
	for (const file of frameFiles) {
		const scaled = scale(readFrame(file));
		const palette = quantize(scaled[0], 128);
		const index = applyPalette(scaled[0], palette);
		gif.writeFrame(index, scaled[1], scaled[2], { palette, delay: Math.round(1000 / fps) });
	}
	gif.finish();
	fs.writeFileSync(gifFile, gif.bytes());
}

function main() {
	const files = walk(artifactsDir);
	const frameDirs = new Map();
	for (const file of files) {
		if (!file.endsWith('.png')) {
			continue;
		}
		const dir = path.dirname(file);
		if (path.basename(dir).startsWith('gif-frames-')) {
			if (!frameDirs.has(dir)) {
				frameDirs.set(dir, []);
			}
			frameDirs.get(dir).push(file);
		}
	}

	if (frameDirs.size === 0) {
		console.log('No GIF frame sequences found in e2e-proof/artifacts. Run `yarn test:e2e` first.');
		return;
	}

	fs.mkdirSync(gifsDir, { recursive: true });
	const manifest = { generatedAt: new Date().toISOString(), proofs: [] };

	for (const [dir, frames] of frameDirs) {
		frames.sort();
		const testDir = path.dirname(dir);
		const label = path.basename(dir).replace(/^gif-frames-/, '');
		const testName = path.basename(testDir).replace(/-+(frontend|admin|setup)$/, '');
		const gif = path.join(gifsDir, `${testName}__${label}.gif`);
		framesToGif(frames, gif);

		const screenshots = walk(testDir)
			.filter((file) => file.endsWith('.png') && !file.startsWith(dir + path.sep))
			.map((file) => path.relative(projectRoot, file));

		manifest.proofs.push({
			test: testName,
			label,
			gif: path.relative(projectRoot, gif),
			frames: frames.length,
			screenshots,
		});
		console.log(
			`GIF  ${path.relative(projectRoot, gif)} (${frames.length} frames, ${(fs.statSync(gif).size / 1024).toFixed(0)} KB)`
		);
	}

	fs.writeFileSync(path.join(gifsDir, 'manifest.json'), JSON.stringify(manifest, null, 2));

	const indexHtml = `<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Breadcrumb Block E2E proofs</title>
<style>body{font-family:sans-serif;margin:2rem}h2{font-size:1rem;margin:1.5rem 0 .5rem}img{max-width:640px;border:1px solid #ccc;display:block;margin:.25rem 0}</style>
</head><body>
<h1>Breadcrumb Block E2E proofs</h1>
<p>${manifest.proofs.length} animated GIF(s) + step screenshots. Generated ${manifest.generatedAt}.</p>
${manifest.proofs
	.map(
		(proof) => `<h2>${proof.test}</h2>
<img src="${path.basename(proof.gif)}" alt="${proof.test} ${proof.label}">
${proof.screenshots.map((s) => `<img src="../${s.replaceAll('\\', '/')}" alt="${proof.test} ${proof.label} screenshot">`).join('\n')}`
	)
	.join('\n')}
</body></html>`;
	fs.writeFileSync(path.join(gifsDir, 'index.html'), indexHtml);
	console.log(`\n${manifest.proofs.length} GIF proof(s) in e2e-proof/gifs + manifest.json + index.html`);
}

main();