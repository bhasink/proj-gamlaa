import { build } from 'esbuild';
import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile, copyFile } from 'node:fs/promises';
import path from 'node:path';

const root = process.cwd();
const outDir = path.join(root, 'public', 'build');
const manifest = {};

async function hashFile(file) {
  const buf = await readFile(file);
  return createHash('sha256').update(buf).digest('hex').slice(0, 10);
}

async function buildJs(input, publicKey) {
  await mkdir(outDir, { recursive: true });
  const temp = path.join(outDir, publicKey.replace(/[\/]/g, '__') + '.tmp.js');
  await build({ entryPoints: [path.join(root, input)], outfile: temp, minify: true, bundle: false, legalComments: 'none' });
  const hash = await hashFile(temp);
  const outName = `${path.basename(input, '.js')}.${hash}.js`;
  const outPath = path.join(outDir, outName);
  await copyFile(temp, outPath);
  manifest[publicKey] = `/build/${outName}`;
}

async function buildCss(input, publicKey) {
  await mkdir(outDir, { recursive: true });
  const temp = path.join(outDir, publicKey.replace(/[\/]/g, '__') + '.tmp.css');
  await build({ entryPoints: [path.join(root, input)], outfile: temp, minify: true, bundle: false, legalComments: 'none' });
  const hash = await hashFile(temp);
  const outName = `${path.basename(input, '.css')}.${hash}.css`;
  const outPath = path.join(outDir, outName);
  await copyFile(temp, outPath);
  manifest[publicKey] = `/build/${outName}`;
}

await buildCss('public/css/design-inspiration.css', 'css/design-inspiration.css');
await buildJs('public/js/design-inspiration.js', 'js/design-inspiration.js');
await buildCss('public/admin-ui/css/admin.css', 'admin-ui/css/admin.css');
await buildJs('public/admin-ui/js/admin.js', 'admin-ui/js/admin.js');

await writeFile(path.join(outDir, 'manifest.json'), JSON.stringify(manifest, null, 2));
console.log('Built assets:', manifest);
