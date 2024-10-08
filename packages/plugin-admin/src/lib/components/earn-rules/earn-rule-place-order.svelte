<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import SettingsInput from "../settings-input.svelte";
	import { Alert } from "../ui/alert";
	import * as Card from "$lib/components/ui/card/index.js";
	import type { GetEarnRuleByIdResponse } from "$lib/modules/settings/types";

	export let minimumOrderAmount: GetEarnRuleByIdResponse[0]["minimumOrderAmount"];
</script>

<Card.Root>
	<Card.Header>
		<Card.Title class="mb-2">{__("Rule settings", "piggy")}</Card.Title>

		<Card.Description>
			<Alert
				type="info"
				description={__(
					"The amount of credits customers will earn will be calculated based on the rules you have set up in the Piggy Dashboard.",
					"piggy"
				)}
			/>
		</Card.Description>
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
