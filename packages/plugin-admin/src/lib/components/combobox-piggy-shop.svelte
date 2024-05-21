<script lang="ts">
	import { Combobox } from '$lib/components/ui/combobox';

	const options = [
		{
			label: 'Mediastap',
			value: 'mediastap'
		},
		{
			label: 'Chris Jayden',
			value: 'chris-jayden'
		},
		{
			label: 'Wonder & Melon',
			value: 'wonder-melon'
		}
	];
</script>

<Combobox items={options} label="shop" />
