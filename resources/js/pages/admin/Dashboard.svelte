<script lang="ts">
    import { onDestroy, onMount } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StageCard from '@/components/StageCard.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        kpis,
        overdueStages,
        nearDeadlineJobs,
    }: {
        kpis: {
            openJobCards: number;
            overdueStages: number;
            pendingDelayReports: number;
            completedJobCards: number;
            nearDeadlineJobs: number;
        };
        overdueStages: Array<{ id: number; stage: { name: string }; job_card: { job_number: string } }>;
        nearDeadlineJobs: Array<{ id: number; job_number: string; promised_delivery_at: string; vehicle: { registration_number: string } }>;
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Admin Dashboard', href: '/admin/dashboard' }];

    let pollInterval: ReturnType<typeof setInterval> | null = null;

    onMount(() => {
        pollInterval = setInterval(() => {
            router.reload({
                only: ['kpis', 'overdueStages', 'nearDeadlineJobs'],
            });
        }, 30000);
    });

    onDestroy(() => {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    });
</script>

<AppHead title="Admin Dashboard" />

<AdminLayout {breadcrumbs}>
    <div class="grid gap-4 md:grid-cols-4">
        <StageCard title="Open Job Cards" status="OPEN" description={`${kpis.openJobCards}`} />
        <StageCard title="Overdue Stages" status="OVERDUE" description={`${kpis.overdueStages}`} />
        <StageCard title="Pending Delays" status="IN_PROGRESS" description={`${kpis.pendingDelayReports}`} />
        <StageCard title="Completed Job Cards" status="COMPLETED" description={`${kpis.completedJobCards}`} />
        <StageCard title="Near Deadline Jobs" status="IN_PROGRESS" description={`${kpis.nearDeadlineJobs}`} />
    </div>

    <section class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold">Overdue stage queue</h2>
        <Accordion multiple>
            {#if overdueStages.length === 0}
                <p class="rounded-lg border px-3 py-2 text-sm text-muted-foreground">No overdue stages.</p>
            {:else}
                {#each overdueStages as item (item.id)}
                    <Accordion.Item value={String(item.id)} class="rounded-lg border px-3 py-2">
                        <Accordion.ItemTrigger class="flex w-full items-center justify-between">
                            <span class="font-medium">{item.job_card.job_number}</span>
                            <span class="text-sm text-muted-foreground">{item.stage.name}</span>
                        </Accordion.ItemTrigger>
                    </Accordion.Item>
                {/each}
            {/if}
        </Accordion>
    </section>

    <section class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold">Jobs nearing deadline (24h)</h2>
        <Accordion multiple>
            {#if nearDeadlineJobs.length === 0}
                <p class="rounded-lg border px-3 py-2 text-sm text-muted-foreground">No jobs nearing deadline.</p>
            {:else}
                {#each nearDeadlineJobs as job (job.id)}
                    <Accordion.Item value={String(job.id)} class="rounded-lg border px-3 py-2">
                        <Accordion.ItemTrigger class="flex w-full items-center justify-between">
                            <span class="font-medium">{job.job_number}</span>
                            <span class="text-sm text-muted-foreground">{new Date(job.promised_delivery_at).toLocaleString()}</span>
                        </Accordion.ItemTrigger>
                        <Accordion.ItemContent class="text-sm text-muted-foreground">
                            Registration: {job.vehicle.registration_number}
                        </Accordion.ItemContent>
                    </Accordion.Item>
                {/each}
            {/if}
        </Accordion>
    </section>
</AdminLayout>
