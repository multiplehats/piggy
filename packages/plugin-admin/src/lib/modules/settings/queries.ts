import type { DefaultError, QueryKey, UndefinedInitialDataOptions } from "@tanstack/svelte-query";
import type { PluginOptionsAdminKeys } from "@leat/types";
import type { GetSettingByIdResponse, GetSettingsParams, GetSettingsResponse } from "./types";
import { SettingsAdminService } from ".";
import { QueryKeys } from "$lib/utils/query-keys";

const service = new SettingsAdminService();

export function getSettingByIdQueryConfig<K extends PluginOptionsAdminKeys>(
	id: K
): UndefinedInitialDataOptions<
	GetSettingByIdResponse<K>,
	DefaultError,
	GetSettingByIdResponse<K>,
	QueryKey
> {
	return {
		queryKey: [QueryKeys.settingById, id],
		retry: false,
		queryFn: async () => await service.getSetting({ id }),
		refetchOnWindowFocus: true,
	};
}

export function getSettingsQueryConfig(): UndefinedInitialDataOptions<
	GetSettingsResponse,
	DefaultError,
	GetSettingsParams,
	QueryKey
> {
	return {
		queryKey: [QueryKeys.settings],
		retry: false,
		queryFn: async () => await service.getAllSettings(),
		refetchOnWindowFocus: true,
	};
}
