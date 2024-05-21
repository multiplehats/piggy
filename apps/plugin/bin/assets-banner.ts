import banner from 'vite-plugin-banner';
import pkg from '../package.json';
import { distDir } from './common';

/**
 * Create a banner for the assets.
 *
 * @param name Name that should appear in the banner.
 * @param outputDirFolder The subfolder in the ./dist folder should the banner be added to.
 * @returns
 */
export const createBanner = (name: string, suffix?: string) => {
	return banner({
		outDir: distDir,
		// content: `/**\n * name: ${name}\n * version: v${pkg.version}\n * description: ${pkg.description}\n * author: ${pkg.author}\n * homepage: ${pkg.homepage}\n */`,
		content: `/**
 * name: ${name + (suffix ? ` - ${suffix}` : '')}
 * version: v${pkg.version} - Build: ${new Date().toISOString()}
 * description: ${pkg.description}
 * author: ${pkg.author}
 * author_url: https://chrisjayden.com
 * homepage: ${pkg.homepage}
 */`,
		verify: true
	}) as string;
};
