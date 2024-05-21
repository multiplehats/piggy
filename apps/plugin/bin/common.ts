import { resolve } from 'node:path';
import path from 'path';

export const pluginDir = resolve(__dirname, '..');
export const distDir = path.join(pluginDir, 'dist');
