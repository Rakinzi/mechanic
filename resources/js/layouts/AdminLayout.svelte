<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();
</script>

<AppLayout {breadcrumbs}>
    <div class="space-y-4 p-4">
        {@render children?.()}
    </div>
</AppLayout>
