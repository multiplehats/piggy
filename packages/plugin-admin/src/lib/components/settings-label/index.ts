import Root from './settings-label.svelte';

interface Props {
	class?: string | undefined;
	id: string;
	tooltip?: string | undefined;
	description?: string | undefined;
	hideLabel?: boolean;
	label: string;
}

export { Root as SettingsLabel, type Props as SettingsLabelProps };
