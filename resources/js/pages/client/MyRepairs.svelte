<script lang="ts">
    import { onDestroy, onMount } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import ClientLayout from '@/layouts/ClientLayout.svelte';
    import type { BreadcrumbItem } from '@/types';
    import { show } from '@/routes/client/repairs';

    let { jobCards }: { jobCards: any[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Repairs', href: '/client/repairs' }];

    let pollInterval: ReturnType<typeof setInterval> | null = null;

    onMount(() => {
        pollInterval = setInterval(() => {
            router.reload({
                only: ['jobCards'],
            });
        }, 45000);
    });

    onDestroy(() => {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    });
</script>

<AppHead title="My Repairs" />

<ClientLayout {breadcrumbs}>
    <section class="space-y-3">
        <Accordion multiple>
            {#each jobCards as jobCard (jobCard.id)}
                <Accordion.Item value={String(jobCard.id)} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between text-left">
                        <span class="font-medium">{jobCard.job_number}</span>
                        <StatusBadge status={jobCard.status} />
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="space-y-1 text-sm text-muted-foreground">
                        <p>{jobCard.vehicle.make} {jobCard.vehicle.model}</p>
                        <a href={show({ jobCard: jobCard.id }).url} class="underline">View progress</a>
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>
</ClientLayout>
