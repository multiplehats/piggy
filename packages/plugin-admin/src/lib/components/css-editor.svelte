<script lang="ts">
  import { onMount } from "svelte";
  import { css } from "@codemirror/lang-css";
  import { Loader2 } from "lucide-svelte";
  import type CodeMirror from "./code-mirror.svelte";

  export let value = "";

  let codeMirror: typeof CodeMirror;
  let lazyLoaded = false;

  onMount(async () => {
    await import("./code-mirror.svelte").then((module) => {
      codeMirror = module.default;
    });

    lazyLoaded = true;
  });
</script>

{#if lazyLoaded}
  <svelte:component this={codeMirror} bind:value lang={css()} />
{:else}
  <div class="inline-flex items-center">
    <Loader2 class="mr-2 animate-spin" />
    <span class="font-bold text-sm">Loading editor...</span>
  </div>
{/if}
