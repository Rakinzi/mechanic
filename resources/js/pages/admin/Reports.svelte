<script lang="ts">
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
            delays_by_technician: Array<{ technician: string; delay_count: number }>;
            common_delay_reasons: Array<{ reason_category: string; total: number }>;
        };
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '/admin/reports' }];

    const reasonLabels: Record<string, string> = {
        PARTS_DELAY: 'Parts Delay',
        EXTERNAL_VENDOR: 'External Vendor',
        REWORK: 'Rework',
        CLIENT_APPROVAL: 'Client Approval',
        ADDITIONAL_DAMAGE: 'Additional Damage',
        OTHER: 'Other',
    };

    const maxTurnaround = $derived(Math.max(...analytics.stage_turnaround.map((i) => i.avg_hours), 1));
    const maxDelays = $derived(Math.max(...analytics.delays_by_technician.map((i) => i.delay_count), 1));
    const maxReasons = $derived(Math.max(...analytics.common_delay_reasons.map((i) => i.total), 1));
</script>

<AppHead title="Reports" />

<AdminLayout {breadcrumbs}>
    <!-- Summary KPIs -->
    <div class="grid gap-4 md:grid-cols-5">
        <StageCard title="Total Job Cards" status="OPEN" description={`${summary.totalJobCards}`} />
        <StageCard title="Completed" status="COMPLETED" description={`${summary.completedJobCards}`} />
        <StageCard title="Overdue Stages" status="OVERDUE" description={`${summary.overdueStages}`} />
        <StageCard title="Pending Delays" status="IN_PROGRESS" description={`${summary.pendingDelayReports}`} />
        <StageCard title="Approved Delays" status="COMPLETED" description={`${summary.approvedDelayReports}`} />
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <!-- Stage turnaround -->
        <div class="rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
            <h2 class="mb-4 text-base font-semibold">Avg. turnaround by stage</h2>
            {#if analytics.stage_turnaround.length === 0}
                <p class="text-sm text-muted-foreground">No data yet.</p>
            {:else}
                <div class="space-y-3">
                    {#each analytics.stage_turnaround as item (item.stage)}
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium">{item.stage}</span>
                                <span class="text-muted-foreground">{item.avg_hours}h</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-blue-500"
                                    style="width: {Math.round((item.avg_hours / maxTurnaround) * 100)}%"
                                ></div>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>

        <!-- Delays by technician -->
        <div class="rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
            <h2 class="mb-4 text-base font-semibold">Delays by technician</h2>
            {#if analytics.delays_by_technician.length === 0}
                <p class="text-sm text-muted-foreground">No data yet.</p>
            {:else}
                <div class="space-y-3">
                    {#each analytics.delays_by_technician as item (item.technician)}
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium">{item.technician}</span>
                                <span class="text-muted-foreground">{item.delay_count} reports</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-amber-500"
                                    style="width: {Math.round((item.delay_count / maxDelays) * 100)}%"
                                ></div>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>
    </div>

    <!-- Common delay reasons -->
    <div class="mt-6 rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
        <h2 class="mb-4 text-base font-semibold">Most common delay reasons</h2>
        {#if analytics.common_delay_reasons.length === 0}
            <p class="text-sm text-muted-foreground">No data yet.</p>
        {:else}
            <div class="space-y-3">
                {#each analytics.common_delay_reasons as item (item.reason_category)}
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium">{reasonLabels[item.reason_category] ?? item.reason_category.replaceAll('_', ' ')}</span>
                            <span class="text-muted-foreground">{item.total}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-red-400"
                                style="width: {Math.round((item.total / maxReasons) * 100)}%"
                            ></div>
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
</AdminLayout>
