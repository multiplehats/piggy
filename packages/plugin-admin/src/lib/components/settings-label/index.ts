import Root from "./settings-label.svelte";

type Props = {
	class?: string | undefined;
	id: string;
	tooltip?: string | undefined;
	description?: string | undefined;
	hideLabel?: boolean;
	label: string;
	optional?: boolean;
};

export { Root as SettingsLabel, type Props as SettingsLabelProps };
