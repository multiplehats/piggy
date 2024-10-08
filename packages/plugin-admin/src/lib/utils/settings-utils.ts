import type { Checkboxes } from "@piggy/types/plugin/settings/adminTypes";

export function noCheckboxSelected(checkboxes: Checkboxes): boolean {
	return Object.values(checkboxes.value).every((status) => status === "off");
}
