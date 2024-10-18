<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import SettingsInput from "../settings-input.svelte";
	import * as Card from "$lib/components/ui/card/index.js";
	import type { GetEarnRuleByIdResponse } from "$lib/modules/settings/types";

	export let socialHandle: GetEarnRuleByIdResponse[0]["socialHandle"];
</script>

<Card.Root>
	<Card.Header>
		<Card.Title>{__("Rule settings", "leat")}</Card.Title>
	</Card.Header>

	<Card.Content>
		<div class="grid gap-6">
			<div class="grid gap-3">
				<SettingsInput {...socialHandle} bind:value={socialHandle.value} />
			</div>
		</div>
	</Card.Content>
</Card.Root>
