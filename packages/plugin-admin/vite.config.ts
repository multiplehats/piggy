import { svelte } from '@sveltejs/vite-plugin-svelte';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [svelte()],
	optimizeDeps: {
		exclude: [
			'codemirror',
			'@codemirror/language-javascript',
			'@codemirror/state',
			'@codemirror/theme-one-dark',
			'@codemirror/lint',
			'@codemirror/language',
			'@codemirror/view',
			'@codemirror/autocomplete',
			'eslint-linter-browserify'
		]
	}
});
