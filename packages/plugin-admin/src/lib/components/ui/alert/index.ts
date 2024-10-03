import Root from "./alert.svelte";

type Props = {
	title?: string | undefined;
	description?: string | undefined;
	type: "success" | "info" | "warning" | "error";
	class?: string;
};

export { Root as Alert, type Props as AlertProps };
