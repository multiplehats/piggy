import { __ } from "@wordpress/i18n";

export function getStatusText(status: string) {
	if (status === "publish") {
		return __("Active", "leat-crm");
	} else if (status === "draft") {
		return __("Inactive", "leat-crm");
	}

	return status;
}
