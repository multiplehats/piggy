<script lang="ts">
	import { Check } from "lucide-svelte";
	import { onboardingSteps } from "$lib/stores/onboarding";
	import { cn } from "$lib/utils/tw";

	let className: string | undefined = undefined;

	export { className as class };
</script>

<nav aria-label="Progress" class={cn(className)}>
	<ol
		role="list"
		class="divide-y divide-gray-300 rounded-md border border-gray-300 md:flex md:divide-y-0"
	>
		{#each $onboardingSteps as { title, status }, i}
			{@const isLastSteps = i === $onboardingSteps.length - 1}

			<li class="relative md:flex md:flex-1">
				<div class={cn("group flex items-center")}>
					<span class="flex select-none items-center px-6 py-4 text-sm font-medium">
						{#if status !== "completed"}
							<span
								class={cn(
									"flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2",
									status === "current"
										? "border-piggy-primary-600"
										: "border-gray-300 group-hover:border-gray-400"
								)}
							>
								<span class="text-gray-500 group-hover:text-gray-900">{i + 1}</span>
							</span>
						{:else}
							<span
								class="bg-piggy-primary-600 group-hover:bg-piggy-primary-800 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
							>
								<Check class="h-6 w-6 text-white"></Check>
							</span>
						{/if}

						<span
							class="ml-4 text-sm font-medium text-gray-500 group-hover:text-gray-900"
						>
							{title}
						</span>
					</span>
				</div>

				<!-- Arrow separator for lg screens and up -->
				{#if !isLastSteps}
					<div
						class="absolute right-0 top-0 hidden h-full w-5 md:block"
						aria-hidden="true"
					>
						<svg
							class="h-full w-full text-gray-300"
							viewBox="0 0 22 80"
							fill="none"
							preserveAspectRatio="none"
						>
							<path
								d="M0 -2L20 40L0 82"
								vector-effect="non-scaling-stroke"
								stroke="currentcolor"
								stroke-linejoin="round"
							/>
						</svg>
					</div>
				{/if}
			</li>
		{/each}
	</ol>
</nav>
