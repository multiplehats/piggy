import Root from './alert.svelte';

interface Props {
	title?: string | undefined;
	description: string;
	type: 'success' | 'info' | 'warning' | 'error';
	class?: string;
}

export { Root as Alert, type Props as AlertProps };
