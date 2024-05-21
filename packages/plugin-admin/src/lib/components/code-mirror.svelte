<script lang="ts">
  import { onMount } from "svelte";
  import type { Extension } from "@codemirror/state";
  import type { LanguageSupport } from "@codemirror/language";
  import { oneDark } from "@codemirror/theme-one-dark";
  import { Loader2 } from "lucide-svelte";
  import type CodeMirror from "svelte-codemirror-editor";

  export let extensions: Extension[] = [];
  export let lang: LanguageSupport;
  export let value = "";

  let codeMirror: typeof CodeMirror
  let lazyLoaded = false;

  onMount(() => {
    import("svelte-codemirror-editor").then((module) => {
      codeMirror = module.default;
      lazyLoaded = true;
    });
  });

  $: extensionsMerged = [...extensions];
</script>

{#if lazyLoaded}
    <svelte:component
      this={codeMirror}
      bind:value
      {lang}
      theme={oneDark}
      extensions={extensionsMerged}
    />
{:else}
  <div class="inline-flex items-center">
    <Loader2 class="mr-2 animate-spin h-5 w-5"  />
    <span class="font-bold text-sm">Loading editor...</span>
  </div>
{/if}
