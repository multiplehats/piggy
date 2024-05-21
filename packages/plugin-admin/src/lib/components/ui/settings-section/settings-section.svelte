<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import { cn } from '$lib/utils/tw.js';
	import { Check, Loader2 } from 'lucide-svelte';
	import Button from '../button/button.svelte';

	export let title: string = '';
	export let onSaved: (() => Promise<void>) | null = null;
	export let loading = false;

	let saved = false;

	const handleOnSave = async () => {
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
	};

	const paddingX = 'px-8 sm:px-6';
	const slugifyTitle = (title: string) => title.toLowerCase().replace(/\s/g, '-');
</script>

<div class={cn('shadow rounded-lg bg-card', $$props.class)}>
	<div class="bg-card">
		{#if title}
			<div class={cn('border-b py-2.5', paddingX)}>
				<h2
					tabindex="-1"
					id={slugifyTitle(title)}
					class="flex items-center outline-none m-0 text-base font-medium leading-6 text-card-foreground"
				>
					{title}
				</h2>

				{#if $$slots.description}
					<p class="mt-1.5 text-sm text-card-foreground">
						<slot name="description" />
					</p>
				{/if}
			</div>
		{/if}

		<div class={cn('bg-card py-6', paddingX)}>
			<slot />
		</div>
	</div>

	{#if onSaved ?? $$slots.actions}
		<div class="px-4 py-2.5 text-left sm:px-6 border-t">
			<slot name="actions">
				<div class="inline-flex items-center">
					<Button size="xs" on:click={handleOnSave} disabled={loading}>
						{#if loading}
							<Loader2 class="mr-2 h-4 w-4 animate-spin" />
						{/if}

						{__('Save changes')}
					</Button>
					{#if saved}
						<div class="inline-flex items-center">
							<Check class="ml-2 text-green-600" />
							<span class="ml-0.text-green-600 font-bold">
								{__('Saved')}
							</span>
						</div>
					{/if}
				</div>
			</slot>
		</div>
	{/if}
</div>
