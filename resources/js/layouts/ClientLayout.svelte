<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { index as repairsIndex } from '@/routes/client/repairs';
    import { index as vehiclesIndex } from '@/routes/client/vehicles';
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
        <nav class="flex gap-2">
            <a href={vehiclesIndex().url} class="rounded-md border px-3 py-1.5 text-sm">My Vehicles</a>
            <a href={repairsIndex().url} class="rounded-md border px-3 py-1.5 text-sm">My Repairs</a>
        </nav>
        {@render children?.()}
    </div>
</AppLayout>
