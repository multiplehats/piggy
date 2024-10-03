<script lang="ts">
	import { onMount } from "svelte";
	import { Link, useLocation } from "svelte-navigator";
	import Logo from "../settings-logo.svelte";
	import NavigationLinkMobile from "./settings-navigation-link-mobile.svelte";
	import NavigationLink from "./settings-navigation-link.svelte";
	import { cn } from "$lib/utils/tw.js";
	import { headerLinks } from "$lib/config/constants";

	const location = useLocation();

	let mobileMenuOpen = false;

	onMount(() => {
		// Close mobile menu when route changes.
		location.subscribe((location) => {
			mobileMenuOpen = false;
		});
	});
</script>

<header class="bg-desert border-b border-gray-200">
	<div class="app-container">
		<div class="flex h-16 justify-between">
			<div class="flex px-2 xl:px-0">
				<div class="flex flex-shrink-0 items-center">
					<Link to="/">
						<Logo />
					</Link>
				</div>
				<nav
					aria-label="Global"
					class="hidden xl:ml-6 xl:flex xl:items-center xl:space-x-4"
				>
					{#each headerLinks as { href, label, target, type }, index (index)}
						<NavigationLink {href} {target} {type} active={$location.pathname === href}
							>{label}</NavigationLink
						>
					{/each}
				</nav>
			</div>

			<div class="flex items-center xl:hidden">
				<!-- Mobile menu button -->
				<button
					type="button"
					class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
					aria-expanded={mobileMenuOpen}
					on:click={() => (mobileMenuOpen = !mobileMenuOpen)}
				>
					<span class="sr-only">Open main menu</span>
					<!-- Heroicon name: outline/bars-3 -->
					<svg
						class="block h-6 w-6"
						xmlns="http://www.w3.org/2000/svg"
						fill="none"
						viewBox="0 0 24 24"
						stroke-width="1.5"
						stroke="currentColor"
						aria-hidden="true"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
						/>
					</svg>
				</button>
			</div>

			<div class={cn("xl:hidden", mobileMenuOpen && "block", !mobileMenuOpen && "hidden")}>
				<div class="fixed inset-0 z-20 bg-black bg-opacity-25" aria-hidden="true" />
				<div
					class="absolute right-0 top-0 z-30 w-full max-w-none origin-top transform p-2 transition"
				>
					<div
						class="divide-y divide-gray-200 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5"
					>
						<div class="pb-2 pt-3">
							<div class="flex items-center justify-between px-4">
								<div>
									<Logo />
								</div>
								<div class="-mr-2">
									<button
										type="button"
										class="inline-flex items-center justify-center rounded-md bg-white p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
										on:click={() => (mobileMenuOpen = !mobileMenuOpen)}
									>
										<span class="sr-only">Close menu</span>
										<!-- Heroicon name: outline/x-mark -->
										<svg
											class="h-6 w-6"
											xmlns="http://www.w3.org/2000/svg"
											fill="none"
											viewBox="0 0 24 24"
											stroke-width="1.5"
											stroke="currentColor"
											aria-hidden="true"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												d="M6 18L18 6M6 6l12 12"
											/>
										</svg>
									</button>
								</div>
							</div>
							<div class="mt-3 space-y-1 px-2">
								{#each headerLinks as { href, label, target, type }, index (index)}
									<NavigationLinkMobile
										{href}
										{target}
										{type}
										active={$location.pathname === href}
									>
										{label}
									</NavigationLinkMobile>
								{/each}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>
