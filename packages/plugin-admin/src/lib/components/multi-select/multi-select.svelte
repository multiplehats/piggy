<script lang="ts">
	import { cn } from '$lib/utils/tw.js';
	import { debounce } from 'lodash-es';
	import { createEventDispatcher, tick } from 'svelte';
	import { flip } from 'svelte/animate';
	import type { DispatchEvents, MultiSelectEvents, ObjectOption, Option } from '.';
	import CircleSpinner from './circle-spinner.svelte';
	import { CrossIcon, DisabledIcon, ExpandIcon } from './icons';
	import Wiggle from './wiggle-spinner.svelte';

	export let activeIndex: number | null = null;
	export let activeOption: Option | null = null;
	export let addOptionMsg = `Create this option...`;
	export let allowUserOptions: boolean | 'append' = false;
	export let autocomplete = `off`;
	export let autoScroll = true;

	export let defaultDisabledTitle = `This option is disabled`;
	export let disabled = false;
	export let disabledInputTitle = `This input is disabled`;
	// case-insensitive equality comparison after string coercion (looking only at the `label` key of object options)
	export let duplicateFunc: (op1: Option, op2: Option) => boolean = (op1, op2) =>
		`${get_label(op1)}`.toLowerCase() === `${get_label(op2)}`.toLowerCase();
	export let duplicateOptionMsg = `This option is already selected`;
	export let duplicates = false; // whether to allow duplicate options
	export let filterFunc = (op: Option, searchText: string): boolean => {
		if (!searchText) return true;
		return `${get_label(op)}`.toLowerCase() !== searchText.toLowerCase();
	};
	export let focusInputOnSelect: boolean | 'desktop' = `desktop`;
	export let form_input: HTMLInputElement | null = null;
	export let id: string | null = null;
	export let input: HTMLInputElement | null = null;
	export let inputClass = ``;
	export let invalid = false;
	export let liActiveOptionClass = ``;
	export let liOptionClass = ``;
	export let liSelectedClass = ``;
	export let loading = false;
	export let matchingOptions: Option[] = [];
	export let maxSelect: number | null = null; // null means there is no upper limit for selected.length
	export let maxSelectMsg: ((current: number, max: number) => string) | null = (
		current: number,
		max: number
	) => (max > 1 ? `${current}/${max}` : ``);
	export let maxSelectMsgClass = ``;
	export let name: string | null = null;
	export let remote = false;
	export let noMatchingOptionsMsg: string = remote
		? 'Type something to search'
		: `No matching options`;
	export let noResultsFoundMsg = `No results found`;
	export let open = false;
	export let options: Option[];
	export let outerDiv: HTMLDivElement | null = null;
	export let outerDivClass = ``;
	export let parseLabelsAsHtml = false; // should not be combined with allowUserOptions!
	export let pattern: string | null = null;
	export let placeholder: string | null = null;
	export let removeAllTitle = `Remove all`;
	export let removeBtnTitle = `Remove`;
	export let minSelect: number | null = null; // null means there is no lower limit for selected.length
	export let required: boolean | number = false;
	export let resetFilterOnAdd = true;
	export let searchText = ``;
	export let selected: Option[] =
		options?.filter((op) => (op as ObjectOption)?.preselected).slice(0, maxSelect ?? undefined) ??
		[];
	export let selectedOptionsDraggable = true;
	export let sortSelected: boolean | ((op1: Option, op2: Option) => number) = false;
	export let ulOptionsClass = ``;
	export let ulSelectedClass = ``;
	export let value: Option | Option[] | null = null;

	let breakpoint = 800;

	// get the label key from an option object or the option itself if it's a string or number
	const get_label = (op: Option) => (op instanceof Object ? op.label : op);

	// if maxSelect=1, value is the single item in selected (or null if selected is empty)
	// this solves both https://github.com/janosh/svelte-multiselect/issues/86 and
	// https://github.com/janosh/svelte-multiselect/issues/136
	$: value = maxSelect === 1 ? selected[0] ?? null : selected;

	let wiggle = false; // controls wiggle animation when user tries to exceed maxSelect

	type $$Events = MultiSelectEvents; // for type-safe event listening on this component

	if (!(options?.length > 0)) {
		if (allowUserOptions ?? loading ?? disabled) {
			options = []; // initializing as array avoids errors when component mounts
		} else {
			// only error for empty options if user is not allowed to create custom
			// options and loading is false
			console.warn(`MultiSelect received no options`);
			options = [];
		}
	}

	if (parseLabelsAsHtml && allowUserOptions) {
		console.warn(
			`Don't combine parseLabelsAsHtml and allowUserOptions. It's susceptible to XSS attacks!`
		);
	}

	if (maxSelect !== null && maxSelect < 1) {
		console.error(`MultiSelect's maxSelect must be null or positive integer, got ${maxSelect}`);
	}

	if (!Array.isArray(selected)) {
		console.error(`MultiSelect's selected prop should always be an array, got ${selected}`);
	}

	if (maxSelect && typeof required === `number` && required > maxSelect) {
		console.error(
			`MultiSelect maxSelect=${maxSelect} < required=${required}, makes it impossible for users to submit a valid form`
		);
	}

	const dispatch = createEventDispatcher<DispatchEvents>();
	let add_option_msg_is_active = false; // controls active state of <li>{addOptionMsg}</li>
	let window_width: number;

	// options matching the current search text
	$: matchingOptions = options.filter(
		(op) => filterFunc(op, searchText) && !selected.map(get_label).includes(get_label(op)) // remove already selected options from dropdown list
	);
	// raise if matchingOptions[activeIndex] does not yield a value
	if (activeIndex !== null && !matchingOptions[activeIndex]) {
		throw `Run time error, activeIndex=${activeIndex} is out of bounds, matchingOptions.length=${matchingOptions.length}`;
	}
	// update activeOption when activeIndex changes
	$: activeOption = activeIndex !== null ? matchingOptions[activeIndex] : null;

	// add an option to selected list
	function add(label: string | number, event: Event) {
		if (maxSelect && maxSelect > 1 && selected.length >= maxSelect) wiggle = true;
		if (!isNaN(Number(label)) && typeof selected.map(get_label)[0] === `number`)
			label = Number(label); // convert to number if possible

		const is_duplicate = selected.some((option) => duplicateFunc(option, label));

		if (
			(maxSelect === null ??
				maxSelect === 1 ??
				// @ts-expect-error - maxSelect is a number here
				selected.length < maxSelect) &&
			(duplicates ?? !is_duplicate)
		) {
			// first check if we find option in the options list

			let option = options.find((op) => get_label(op) === label);
			if (
				!option && // this has the side-effect of not allowing to user to add the same
				// custom option twice in append mode
				[true, `append`].includes(allowUserOptions) &&
				searchText.length > 0
			) {
				// user entered text but no options match, so if allowUserOptions=true | 'append', we create
				// a new option from the user-entered text
				if (typeof options[0] === `object`) {
					// if 1st option is an object, we create new option as object to keep type homogeneity
					option = { label: searchText, value: searchText };
				} else {
					if ([`number`, `undefined`].includes(typeof options[0]) && !isNaN(Number(searchText))) {
						// create new option as number if it parses to a number and 1st option is also number or missing
						option = Number(searchText);
					} else option = searchText; // else create custom option as string
				}
				if (allowUserOptions === `append`) options = [...options, option];
			}
			if (option === undefined) {
				throw `Run time error, option with label ${label} not found in options list`;
			}
			if (resetFilterOnAdd) searchText = ``; // reset search string on selection
			if ([``, undefined, null].includes(option as any)) {
				console.error(
					`MultiSelect: encountered missing option with label ${label} (or option is poorly labeled)`
				);
				return;
			}
			if (maxSelect === 1) {
				// for maxselect = 1 we always replace current option with new one
				selected = [option];
			} else {
				selected = [...selected, option];
				if (sortSelected === true) {
					selected = selected.sort((op1: Option, op2: Option) => {
						const [label1, label2] = [get_label(op1), get_label(op2)];
						// coerce to string if labels are numbers
						return `${label1}`.localeCompare(`${label2}`);
					});
				} else if (typeof sortSelected === `function`) {
					selected = selected.sort(sortSelected);
				}
			}
			if (selected.length === maxSelect) close_dropdown(event);
			else if (
				focusInputOnSelect === true ??
				(focusInputOnSelect === `desktop` && window_width > breakpoint)
			) {
				input?.focus();
			}
			dispatch(`add`, { option });
			dispatch(`change`, { option, type: `add` });

			invalid = false; // reset error status whenever new items are selected
			form_input?.setCustomValidity(``);
		}
	}

	// remove an option from selected list
	function remove(label: string | number) {
		if (selected.length === 0) return;

		selected.splice(selected.map(get_label).lastIndexOf(label), 1);
		selected = selected; // Svelte rerender after in-place splice

		const option =
			options.find((option) => get_label(option) === label) ??
			// if option with label could not be found but allowUserOptions is truthy,
			// assume it was created by user and create correspondidng option object
			// on the fly for use as event payload
			(allowUserOptions && { label, value: label });

		if (!option) {
			return console.error(`MultiSelect: option with label ${label} not found`);
		}

		dispatch(`remove`, { option });
		dispatch(`change`, { option, type: `remove` });
		invalid = false; // reset error status whenever items are removed
		form_input?.setCustomValidity(``);
	}

	function handle_focus(event: Event) {
		open_dropdown(event);

		if (remote && !searchText) {
			options = [];
		}
	}

	function open_dropdown(event: Event) {
		if (disabled) return;
		open = true;
		if (!(event instanceof FocusEvent)) {
			// avoid double-focussing input when event that opened dropdown was already input FocusEvent
			input?.focus();
		}
		dispatch(`open`, { event });
	}

	function close_dropdown(event: Event) {
		open = false;
		input?.blur();
		activeOption = null;
		dispatch(`close`, { event });
	}

	// handle all keyboard events this component receives
	async function handle_keydown(event: KeyboardEvent) {
		// on escape or tab out of input: dismiss options dropdown and reset search text
		if (event.key === `Escape` ?? event.key === `Tab`) {
			close_dropdown(event);
			searchText = ``;
		}
		// on enter key: toggle active option and reset search text
		else if (event.key === `Enter`) {
			event.preventDefault(); // prevent enter key from triggering form submission

			if (activeOption) {
				const label = get_label(activeOption);
				selected.map(get_label).includes(label) ? remove(label) : add(label, event);
				searchText = ``;
			} else if (allowUserOptions && searchText.length > 0) {
				// user entered text but no options match, so if allowUserOptions is truthy, we create new option
				add(searchText, event);
			}
			// no active option and no search text means the options dropdown is closed
			// in which case enter means open it
			else open_dropdown(event);
		}
		// on up/down arrow keys: update active option
		else if ([`ArrowDown`, `ArrowUp`].includes(event.key)) {
			// if no option is active yet, but there are matching options, make first one active
			if (activeIndex === null && matchingOptions.length > 0) {
				activeIndex = 0;
				return;
			} else if (allowUserOptions && searchText.length > 0) {
				// if allowUserOptions is truthy and user entered text but no options match, we make
				// <li>{addUserMsg}</li> active on keydown (or toggle it if already active)
				add_option_msg_is_active = !add_option_msg_is_active;
				return;
			} else if (activeIndex === null) {
				// if no option is active and no options are matching, do nothing
				return;
			}
			// if none of the abvove special cases apply, we make next/prev option
			// active with wrap around at both ends
			const increment = event.key === `ArrowUp` ? -1 : 1;

			activeIndex = (activeIndex + increment) % matchingOptions.length;
			// in JS % behaves like remainder operator, not real modulo, so negative numbers stay negative
			// need to do manual wrap around at 0
			if (activeIndex < 0) activeIndex = matchingOptions.length - 1;

			if (autoScroll) {
				await tick();
				const li = document.querySelector(`ul.options > li.active`);
				// @ts-ignore - scrollIntoViewIfNeeded is not a standard method
				if (li) li.scrollIntoViewIfNeeded?.();
			}
		}
		// on backspace key: remove last selected option
		else if (event.key === `Backspace` && selected.length > 0 && !searchText) {
			remove(selected.map(get_label).at(-1)!);
		}
	}

	const handle_input = debounce((event: Event) => {
		const oldSearchText = searchText;
		searchText = (event.target as HTMLInputElement).value;

		dispatch('search', { query: searchText });
	}, 100);

	function remove_all() {
		dispatch(`removeAll`, { options: selected });
		dispatch(`change`, { options: selected, type: `removeAll` });
		selected = [];
		searchText = ``;
	}

	$: is_selected = (label: string | number) => selected.map(get_label).includes(label);

	const if_enter_or_space = (handler: () => void) => (event: KeyboardEvent) => {
		if ([`Enter`, `Space`].includes(event.code)) {
			event.preventDefault();
			handler();
		}
	};

	function on_click_outside(event: MouseEvent | TouchEvent) {
		if (outerDiv && !outerDiv.contains(event.target as Node)) {
			close_dropdown(event);
		}
	}

	let drag_idx: number | null = null;
	// event handlers enable dragging to reorder selected options
	const drop = (target_idx: number) => (event: DragEvent) => {
		if (!event.dataTransfer) return;
		event.dataTransfer.dropEffect = `move`;
		const start_idx = parseInt(event.dataTransfer.getData(`text/plain`));
		const new_selected = selected;

		if (start_idx < target_idx) {
			new_selected.splice(target_idx + 1, 0, new_selected[start_idx]);
			new_selected.splice(start_idx, 1);
		} else {
			new_selected.splice(target_idx, 0, new_selected[start_idx]);
			new_selected.splice(start_idx + 1, 1);
		}
		selected = new_selected;
		drag_idx = null;
	};

	const dragstart = (idx: number) => (event: DragEvent) => {
		if (!event.dataTransfer) return;
		// only allow moving, not copying (also affects the cursor during drag)
		event.dataTransfer.effectAllowed = `move`;
		event.dataTransfer.dropEffect = `move`;
		event.dataTransfer.setData(`text/plain`, `${idx}`);
	};
</script>

<svelte:window
	on:click={on_click_outside}
	on:touchstart={on_click_outside}
	bind:innerWidth={window_width}
/>

<!-- svelte-ignore a11y-no-static-element-interactions -->
<div
	bind:this={outerDiv}
	class:disabled
	class:single={maxSelect === 1}
	aria-expanded={open}
	aria-multiselectable={typeof maxSelect === `number` && maxSelect > 1}
	class={cn(
		'multiselect relative items-center flex cursor-text box-border background-white border  w-full focus-within:border-blue-500 dark:focus-within:border-blue-700 disabled:cursor-not-allowed disabled:opacity-50 focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500 bg-gray-white text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400  dark:border-gray-600 p-1 text-sm rounded-lg border-gray-300',
		outerDivClass,
		open && 'z-10'
	)}
	on:mouseup|stopPropagation={open_dropdown}
	title={disabled ? disabledInputTitle : null}
	aria-disabled={disabled ? `true` : null}
	data-id={id}
>
	<!-- bind:value={selected} prevents form submission if required prop is true and no options are selected -->
	<input
		{name}
		required={Boolean(required)}
		value={typeof required === 'number' && selected.length >= required
			? JSON.stringify(selected)
			: null}
		tabindex="-1"
		aria-hidden="true"
		aria-label="ignore this, used only to prevent form submission if select is required but empty"
		class="sr-only"
		bind:this={form_input}
		on:invalid={() => {
			invalid = true;
			let msg = '';

			if (maxSelect && maxSelect > 1 && typeof required === 'number' && required > 1) {
				msg = `Please select between ${required} and ${maxSelect} options`;
			} else if (typeof required === 'number' && required > 1) {
				msg = `Please select at least ${required} options`;
			} else {
				msg = `Please select an option`;
			}
			form_input?.setCustomValidity(msg);
		}}
	/>

	<ExpandIcon width="15px" style="min-width: 1em; padding: 0 1pt;" />

	<ul class={cn('flex flex-1 p-0 m-0 flex-wrap', ulSelectedClass)}>
		{#each selected as option, idx (get_label(option))}
			<li
				class={cn(
					'items-center rounded-lg flex m-1 leading-normal whitespace-nowrap bg-secondary py-1 px-2 text-foreground-secondary text-xs',
					drag_idx === idx && 'bg-black/20',
					liSelectedClass
				)}
				animate:flip={{ duration: 100 }}
				draggable={selectedOptionsDraggable}
				on:dragstart={dragstart(idx)}
				on:drop|preventDefault={drop(idx)}
				on:dragenter={() => (drag_idx = idx)}
				on:dragover|preventDefault={() => (drag_idx = idx)}
			>
				<slot name="selected" {option} {idx}>
					{#if parseLabelsAsHtml}
						{@html get_label(option)}
					{:else}
						{get_label(option)}
					{/if}
				</slot>

				{#if !disabled && typeof minSelect === 'number' && selected.length > minSelect}
					<button
						on:mouseup|stopPropagation={() => remove(get_label(option))}
						on:keydown={if_enter_or_space(() => remove(get_label(option)))}
						type="button"
						class="flex duration-200 hover:bg-black/10 rounded-full w-4 h-4 text-inherit bg-transparent cursor-pointer outline-none p-0 ml-1"
						title="{removeBtnTitle} {get_label(option)}"
					>
						<slot name="remove-icon">
							<CrossIcon width="15px" />
						</slot>
					</button>
				{/if}
			</li>
		{/each}

		<li style="display: contents;">
			<input
				class={cn(
					'border-none outline-none bg-transparent bg-none flex-1 min-w-[2em] pl-2 text-black dark:text-white text-sm cursor-inherit rounded-none',
					inputClass
				)}
				bind:this={input}
				{autocomplete}
				bind:value={searchText}
				on:mouseup|self|stopPropagation={open_dropdown}
				on:keydown|stopPropagation={handle_keydown}
				on:input={handle_input}
				on:focus={handle_focus}
				{id}
				{disabled}
				{pattern}
				placeholder={remote ? placeholder : selected.length === 0 ? placeholder : null}
				aria-invalid={invalid ? `true` : null}
				on:drop|preventDefault
				on:focus
				on:blur
				on:change
				on:click
				on:keydown
				on:keyup
				on:mousedown
				on:mouseenter
				on:mouseleave
				on:touchcancel
				on:touchend
				on:touchmove
				on:touchstart
			/>
			<!-- the above on:* lines forward potentially useful DOM events -->
		</li>
	</ul>
	{#if loading}
		<slot name="spinner">
			<CircleSpinner />
		</slot>
	{/if}
	{#if disabled}
		<slot name="disabled-icon">
			<DisabledIcon width="14pt" style="margin: 0 2pt;" data-name="disabled-icon" />
		</slot>
	{:else if selected.length > 0}
		{#if maxSelect && (maxSelect > 1 ?? maxSelectMsg)}
			<Wiggle bind:wiggle angle={20}>
				<span class="max-select-msg {maxSelectMsgClass}">
					{maxSelectMsg?.(selected.length, maxSelect)}
				</span>
			</Wiggle>
		{/if}
		{#if maxSelect !== 1 && selected.length > 1}
			<button
				type="button"
				class="border border-transparent bg-transparent p-0.5 rounded-md text-red-600 cursor-pointer mx-1"
				title={removeAllTitle}
				on:mouseup|stopPropagation={remove_all}
				on:keydown={if_enter_or_space(remove_all)}
			>
				<slot name="remove-icon">
					<CrossIcon width="15px" />
				</slot>
			</button>
		{/if}
	{/if}

	<!-- only render options dropdown if options or searchText is not empty needed to avoid briefly flashing empty dropdown -->
	{#if searchText ?? options?.length > 0}
		<ul
			class={cn(
				'options list-none top-full max-h-60 mt-1 px-0 py-1 left-0 w-full absolute rounded-md ring-1 ring-black ring-opacity-5 focus:outline-none overflow-auto bg-white shadow-md overscroll-none transition-all duration-200',
				!matchingOptions.length && open && '!p-2',
				!open && 'hidden',
				ulOptionsClass
			)}
		>
			{#each matchingOptions as option, idx}
				{@const {
					label,
					disabled = null,
					title = null,
					selectedTitle = null,
					disabledTitle = defaultDisabledTitle
				} = option instanceof Object ? option : { label: option }}
				{@const active = activeIndex === idx}
				<!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
				<li
					on:mousedown|stopPropagation
					on:mouseup|stopPropagation={(event) => {
						if (!disabled) add(label, event);
					}}
					title={disabled
						? disabledTitle
						: is_selected(label) && selectedTitle
						? selectedTitle
						: title}
					class:selected={is_selected(label)}
					class:active
					class:disabled
					class="{liOptionClass} {active ? liActiveOptionClass : ``}"
					on:mouseover={() => {
						if (!disabled) activeIndex = idx;
					}}
					on:focus={() => {
						if (!disabled) activeIndex = idx;
					}}
					on:mouseout={() => (activeIndex = null)}
					on:blur={() => (activeIndex = null)}
				>
					<slot name="option" {option} {idx}>
						{#if parseLabelsAsHtml}
							{@html get_label(option)}
						{:else}
							{get_label(option)}
						{/if}
					</slot>
				</li>
			{:else}
				{#if allowUserOptions && searchText}
					<!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
					<li
						on:mousedown|stopPropagation
						on:mouseup|stopPropagation={(event) => add(searchText, event)}
						title={addOptionMsg}
						class:active={add_option_msg_is_active}
						on:mouseover={() => (add_option_msg_is_active = true)}
						on:focus={() => (add_option_msg_is_active = true)}
						on:mouseout={() => (add_option_msg_is_active = false)}
						on:blur={() => (add_option_msg_is_active = false)}
					>
						{!duplicates && selected.some((option) => duplicateFunc(option, searchText))
							? duplicateOptionMsg
							: addOptionMsg}
					</li>
				{:else if remote && !loading && !matchingOptions.length}
					{noResultsFoundMsg}
				{:else}
					<span>{noMatchingOptionsMsg}</span>
				{/if}
			{/each}
		</ul>
	{/if}
</div>

<style>
	:where(li[draggable]) {
		cursor: grab;
	}

	:where(div.multiselect > ul.options.hidden) {
		visibility: hidden;
		opacity: 0;
		transform: translateY(50px);
	}
	:where(div.multiselect > ul.options > li) {
		padding: 3pt 2ex;
		cursor: pointer;
		scroll-margin: var(--sms-options-scroll-margin, 100px);
	}
	/* for noOptionsMsg */
	:where(div.multiselect > ul.options span) {
		padding: 3pt 2ex;
	}
	:where(div.multiselect > ul.options > li.selected) {
		background: var(--sms-li-selected-bg);
		color: var(--sms-li-selected-color);
	}
	:where(div.multiselect > ul.options > li.active) {
		background: var(--sms-li-active-bg, var(--sms-active-color, rgba(0, 0, 0, 0.15)));
	}
	:where(div.multiselect > ul.options > li.disabled) {
		cursor: not-allowed;
		background: var(--sms-li-disabled-bg, #f5f5f6);
		color: var(--sms-li-disabled-text, #b8b8b8);
	}

	:where(span.max-select-msg) {
		padding: 0 3pt;
	}
</style>
