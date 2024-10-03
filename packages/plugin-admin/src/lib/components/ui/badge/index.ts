import { tv } from "tailwind-variants";
import type { VariantProps } from "tailwind-variants";

export { default as Badge } from "./badge.svelte";

export const badgeVariants = tv({
	base: "focus:ring-ring inline-flex select-none items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2",
	variants: {
		variant: {
			default: "bg-primary hover:bg-primary/80 text-primary-foreground border-transparent",
			secondary:
				"bg-secondary hover:bg-secondary/80 text-secondary-foreground border-transparent",
			destructive:
				"bg-destructive hover:bg-destructive/80 text-destructive-foreground border-transparent",
			outline: "text-foreground",
		},
	},
	defaultVariants: {
		variant: "default",
	},
});

export type Variant = VariantProps<typeof badgeVariants>["variant"];
