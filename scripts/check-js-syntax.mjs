import { spawnSync } from 'node:child_process';
import { readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const roots = ['public/js'];
const files = [];

function walk(directory) {
    for (const entry of readdirSync(directory)) {
        const path = join(directory, entry);
        const stat = statSync(path);
        if (stat.isDirectory()) {
            walk(path);
        } else if (path.endsWith('.js')) {
            files.push(path);
        }
    }
}

roots.forEach(walk);

let failed = false;
for (const file of files) {
    const result = spawnSync(process.execPath, ['--check', file], { stdio: 'inherit' });
    if (result.status !== 0) {
        failed = true;
    }
}

if (failed) {
    process.exit(1);
}

console.log(`Checked ${files.length} JavaScript files.`);
