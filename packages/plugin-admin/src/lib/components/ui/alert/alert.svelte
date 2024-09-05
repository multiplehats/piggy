<script lang="ts">
	import { cn } from '$lib/utils/tw';
	import AlertCircle from 'lucide-svelte/icons/circle-alert';
	import CheckCircle from 'lucide-svelte/icons/circle-check';
	import CircleHelp from 'lucide-svelte/icons/circle-help';
	import CircleX from 'lucide-svelte/icons/circle-x';
	import type { AlertProps } from '.';

	type $$Props = AlertProps;

	let className: $$Props['class'] = undefined;

	export { className as class };
	export let description: $$Props['description'] = undefined;
	export let title: $$Props['title'] = undefined;
	export let type: $$Props['type'] = 'info';
</script>

<div
	class={cn(
		'rounded-md bg-gray-50 p-4',
		type === 'error' && 'bg-red-50',
		type === 'success' && 'bg-green-50',
		type === 'warning' && 'bg-yellow-50',
		type === 'info' && 'bg-blue-50',
		className
	)}
>
	<div class="flex">
		<div class="flex">
			<div class="flex-shrink-0">
				{#if type === 'error'}
					<CircleX class="h-5 w-5 text-red-400" />
				{:else if type === 'warning'}
					<AlertCircle class="h-5 w-5 text-yellow-400" />
				{:else if type === 'info'}
					<CircleHelp class="h-5 w-5 text-blue-400" />
				{:else if type === 'success'}
					<CheckCircle class="h-5 w-5 text-green-400" />
				{/if}
			</div>
		</div>

		<div class="ml-3">
			{#if title}
				<p
					class={cn(
						'text-sm font-medium text-gray-800',
						type === 'error' && 'text-red-800',
						type === 'success' && 'text-green-800',
						type === 'warning' && 'text-yellow-800',
						type === 'info' && 'text-blue-800'
					)}
				>
					{title}
				</p>
			{/if}

			<div
				class={cn(
					'text-sm text-gray-700',
					title && 'mt-2',
					type === 'error' && 'text-red-700',
					type === 'success' && 'text-green-700',
					type === 'warning' && 'text-yellow-700',
					type === 'info' && 'text-blue-700'
				)}
			>
				<p>
					{#if description}
						{description}
					{:else}
						<slot />
					{/if}
				</p>
			</div>
		</div>
	</div>
</div>
