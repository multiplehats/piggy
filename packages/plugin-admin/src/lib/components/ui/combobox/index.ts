import Root from './combobox.svelte';

interface Item {
	value: string;
	label: string;
}

interface Props {
	value: string | undefined | null;
	items: Item[];
	itemName: string;
	widthClass?: string | undefined;
	class?: string | undefined;
	noResultsText?: string | undefined;
}

export { Root, Root as Combobox, type Props as ComboboxProps, type Item as ComboboxItem };
