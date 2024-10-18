<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import type { NotUndefined } from "@leat/types";
	import SettingsInput from "$lib/components/settings-input.svelte";
	import * as Card from "$lib/components/ui/card/index.js";
	import type { GetSpendRuleByIdResponse } from "$lib/modules/settings/types";

	export let minimumPurchaseAmount: NotUndefined<
		GetSpendRuleByIdResponse[0]["minimumPurchaseAmount"]
	>;
</script>

<Card.Root>
	<Card.Header>
		<Card.Title class="mb-2">{__("Rule settings", "leat")}</Card.Title>
	</Card.Header>

	<Card.Content>
		<div class="grid">
			<SettingsInput
				{...minimumPurchaseAmount}
				type="number"
				attributes={{ min: 0 }}
				bind:value={minimumPurchaseAmount.value}
			></SettingsInput>
		</div>
	</Card.Content>
</Card.Root>
