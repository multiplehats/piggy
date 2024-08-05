import type { Button as ButtonPrimitive } from 'bits-ui';
import Root from './button.svelte';

type ButtonVariants = 'primary' | 'secondary';

type Props = ButtonPrimitive.Props & {
	variant?: ButtonVariants;
	loading?: boolean;
};

type Events = ButtonPrimitive.Events;

export {
	Root,
	type Props,
	type Events,
	//
	Root as Button,
	type Props as ButtonProps,
	type Events as ButtonEvents
};
