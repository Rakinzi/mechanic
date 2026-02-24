<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbItem } from '@/types';
    import { index as assignedStages } from '@/routes/mechanic/assigned-stages';

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
        <nav class="flex gap-2">
            <a href={assignedStages().url} class="rounded-md border px-3 py-1.5 text-sm">My Assigned Stages</a>
        </nav>
        {@render children?.()}
    </div>
</AppLayout>
