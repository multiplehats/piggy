<script lang="ts">
	import { currentLanguage, pluginSettings } from '$lib/modules/settings';
	import { replaceStrings, type ReplaceStringsObj } from '@piggy/lib';
</script>

<section>
	<div class="piggy-dashboard__container">
		<h2 class="piggy-dashboard__header">
			You have {window.piggyData.contact.balance.credits}
			{$pluginSettings?.credits_name?.[currentLanguage]}
		</h2>

		<nav class="piggy-dashboard__nav">
			<ul class="piggy-dashboard__list">
				<li>
					<button class="piggy-dashboard__item"
						><svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							class="full-page__menu-icon-visibility"
							><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path
								d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1"
							></path><path d="M12 7v10"></path></svg
						>Earn Puntjes
					</button>
				</li>
				<li>
					<button class="piggy-dashboard__item"
						><svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							class="full-page__menu-icon-visibility"
							><path
								d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"
							></path><path d="M9 11v-5a3 3 0 0 1 6 0v5"></path></svg
						>Rewards
					</button>
				</li>
				<li>
					<button class="piggy-dashboard__item"
						><svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							class="full-page__menu-icon-visibility"
							><path d="M12 8l0 4l2 2"></path><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"
							></path></svg
						>Your activity
					</button>
				</li>
			</ul>
		</nav>
	</div>
</section>

<style lang="postcss">
	.piggy-dashboard__container {
		text-align: center;
	}

	.piggy-dashboard__header {
		margin-bottom: 40px;
		font-size: 1.5rem;
		margin: 0;
	}

	.piggy-dashboard__nav {
		justify-content: center;
		display: flex;
		align-items: center;
	}

	.piggy-dashboard__list {
		display: inline-flex;
		border-bottom: 1px solid #e5e5e5;
		gap: 6px;
		justify-content: center;
		flex-wrap: wrap;
		list-style-type: none;
	}

	.piggy-dashboard__item {
		outline: none;
		display: inline-flex;
		cursor: pointer;
		align-items: center;
		gap: 8px;
		padding-top: 8px;
		padding-bottom: 8px;
		letter-spacing: 0.025em;
		border-radius: 4px;
		border: 1px solid #e5e5e5;
		background-color: #f9f9f9;
	}
</style>
