import { derived, writable } from "svelte/store";
import type { GetContactResponse } from "$lib/modules/leat/types";

export const contactStore = writable<GetContactResponse | null>(null);

export const hasLeatAccount = derived(contactStore, ($contact) => $contact?.contact !== null);
