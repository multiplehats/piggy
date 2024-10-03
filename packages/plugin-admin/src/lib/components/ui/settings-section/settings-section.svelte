<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import { Check } from "lucide-svelte";
	import Button from "../button/button.svelte";
	import { cn } from "$lib/utils/tw.js";

	export let title: string = "";
	export let onSaved: (() => Promise<void>) | null = null;
	export let loading = false;

	let saved = false;

	async function handleOnSave() {
		if (onSaved) {
			try {
				saved = false;
				loading = true;
				await onSaved();
				saved = true;
				loading = false;
			} catch (error) {
				loading = false;
				saved = false;
			}
		}
	}

	const paddingX = "px-8 sm:px-6";
	const slugifyTitle = (title: string) => title.toLowerCase().replace(/\s/g, "-");
</script>

<div class={cn("bg-card rounded-lg shadow", $$props.class)}>
	<div class="bg-card">
		{#if title}
			<div class={cn("border-b py-2.5", paddingX)}>
				<h2
					tabindex="-1"
					id={slugifyTitle(title)}
					class="text-card-foreground m-0 flex items-center text-base font-medium leading-6 outline-none"
				>
					{title}
				</h2>

				{#if $$slots.description}
					<p class="text-card-foreground mt-1.5 text-sm">
						<slot name="description" />
					</p>
				{/if}
			</div>
		{/if}

		<div class={cn("bg-card py-6", paddingX)}>
			<slot />
		</div>
	</div>

	{#if onSaved ?? $$slots.actions}
		<div class="border-t px-4 py-2.5 text-left sm:px-6">
			<slot name="actions">
				<div class="inline-flex items-center">
					<Button size="xs" {loading} on:click={handleOnSave}>
						{__("Save changes")}
					</Button>

					{#if saved}
						<div class="inline-flex items-center">
							<Check class="ml-2 text-green-600" />
							<span class="ml-0.text-green-600 font-bold">
								{__("Saved")}
							</span>
						</div>
					{/if}
				</div>
			</slot>
		</div>
	{/if}
</div>
