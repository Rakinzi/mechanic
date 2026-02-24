<script lang="ts">
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StageCard from '@/components/StageCard.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        summary,
        analytics,
    }: {
        summary: Record<string, number>;
        analytics: {
            stage_turnaround: Array<{ stage: string; avg_hours: number }>;
            delays_by_mechanic: Array<{ mechanic: string; delay_count: number }>;
            common_delay_reasons: Array<{ reason_category: string; total: number }>;
        };
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '/admin/reports' }];

</script>

<AppHead title="Reports" />

<AdminLayout {breadcrumbs}>
    <div class="grid gap-4 md:grid-cols-5">
        <StageCard title="Total Job Cards" status="OPEN" description={`${summary.totalJobCards}`} />
        <StageCard title="Completed" status="COMPLETED" description={`${summary.completedJobCards}`} />
        <StageCard title="Overdue Stages" status="OVERDUE" description={`${summary.overdueStages}`} />
        <StageCard title="Pending Delays" status="IN_PROGRESS" description={`${summary.pendingDelayReports}`} />
        <StageCard title="Approved Delays" status="COMPLETED" description={`${summary.approvedDelayReports}`} />
    </div>

    <section class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold">Average turnaround by stage (hours)</h2>
        <Accordion multiple>
            {#each analytics.stage_turnaround as item (item.stage)}
                <Accordion.Item value={item.stage} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between">
                        <span class="font-medium">{item.stage}</span>
                        <span class="text-sm text-muted-foreground">{item.avg_hours}</span>
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="pt-2 text-sm text-muted-foreground">
                        Average completion time for {item.stage}: {item.avg_hours} hours.
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>

    <section class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold">Delays by mechanic</h2>
        <Accordion multiple>
            {#each analytics.delays_by_mechanic as item (item.mechanic)}
                <Accordion.Item value={item.mechanic} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between">
                        <span class="font-medium">{item.mechanic}</span>
                        <span class="text-sm text-muted-foreground">{item.delay_count}</span>
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="pt-2 text-sm text-muted-foreground">
                        Total delay reports submitted: {item.delay_count}.
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>

    <section class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold">Most common delay reasons</h2>
        <Accordion multiple>
            {#each analytics.common_delay_reasons as item (item.reason_category)}
                <Accordion.Item value={item.reason_category} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between">
                        <span class="font-medium">{item.reason_category}</span>
                        <span class="text-sm text-muted-foreground">{item.total}</span>
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="pt-2 text-sm text-muted-foreground">
                        This reason appears in {item.total} delay report(s).
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>
</AdminLayout>
