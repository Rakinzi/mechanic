<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title,
        children,
    }: {
        title: string;
        children?: Snippet;
    } = $props();
</script>

<section class="space-y-3 rounded-lg border p-4">
    <h3 class="text-base font-semibold">{title}</h3>
    {@render children?.()}
</section>
