<script lang="ts">
	import { createQuery } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import * as Card from '$lib/components/ui/card/index.js';
	import * as Table from '$lib/components/ui/table/index.js';
	import { getEarnRulesQueryConfig } from '$lib/modules/settings/queries';
	import { useNavigate } from 'svelte-navigator';
	import { api } from '@piggy/lib';

	const navigate = useNavigate();
	const query = createQuery(getEarnRulesQueryConfig());
</script>

{#if $query?.data}
	<Card.Root class="xl:col-span-2">
		<Card.Header class="flex flex-row items-center">
			<div class="grid gap-2">
				<Card.Title>{__('Earn rules')}</Card.Title>
				<Card.Description>Earn rules from your store.</Card.Description>
			</div>
		</Card.Header>

		<Card.Content>
			<Table.Root>
				<Table.Header>
					<Table.Row>
						<Table.Head>Title</Table.Head>

						<Table.Head>Created At</Table.Head>
						<Table.Head class="text-right">Points</Table.Head>
					</Table.Row>
				</Table.Header>
				<Table.Body>
					{#each $query.data as rule}
						<Table.Row class="cursor-pointer" on:click={() => navigate(`earn-rules/${rule.id}`)}>
							<Table.Cell>
								<div class="font-medium">{@html rule.title.value}</div>
							</Table.Cell>

							<Table.Cell>
								{new Date(rule.createdAt).toLocaleDateString(undefined, {
									year: 'numeric',
									month: 'long',
									day: 'numeric'
								})}
							</Table.Cell>

							<Table.Cell class="text-right">{rule.points ?? 'N/A'}</Table.Cell>
						</Table.Row>
					{/each}
				</Table.Body>
			</Table.Root>
		</Card.Content>
	</Card.Root>
{/if}
