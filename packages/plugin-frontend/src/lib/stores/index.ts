import { derived, writable } from "svelte/store";
import type { GetContactResponse } from "$lib/modules/piggy/types";

export const contactStore = writable<GetContactResponse | null>(null);

export const hasPiggyAccount = derived(contactStore, ($contact) => $contact?.contact !== null);
