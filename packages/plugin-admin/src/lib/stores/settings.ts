import { get, writable } from "svelte/store";
import type { PluginOptionsAdmin, PluginOptionsAdminKeys } from "@leat/types";
import type { GetSettingsResponse } from "$lib/modules/settings/types";

// Settings State

export const settingsState = writable<GetSettingsResponse>();
