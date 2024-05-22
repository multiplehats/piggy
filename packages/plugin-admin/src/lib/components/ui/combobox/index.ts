import Root from './combobox.svelte';

interface Item {
	value: string;
	label: string;
}

interface Props {
	id?: string | undefined;
	items: Item[];
	itemName: string;
	widthClass?: string | undefined;
	class?: string | undefined;
}

export { Root, Root as Combobox, type Props as ComboboxProps, type Item as ComboboxItem };
