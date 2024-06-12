<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import * as Card from '$lib/components/ui/card/index.js';
	import type { GetEarnRuleByIdResponse } from '$lib/modules/settings/types';
	import SettingsInput from '../settings-input.svelte';

	export let minimumOrderAmount: GetEarnRuleByIdResponse[0]['minimumOrderAmount'];
</script>

<Card.Root>
	<Card.Header>
		<Card.Title>{__('Rule settings', 'piggy')}</Card.Title>
	</Card.Header>

	<Card.Content>
		<div class="grid gap-6">
			<div class="grid gap-3">
				<div class="">
					<SettingsInput
						{...minimumOrderAmount}
						withVisibility={true}
						type="number"
						bind:value={minimumOrderAmount.value}
					/>
				</div>
			</div>
		</div>
	</Card.Content>
</Card.Root>
