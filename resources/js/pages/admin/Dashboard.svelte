<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AlertTriangle from 'lucide-svelte/icons/triangle-alert';
    import Clock from 'lucide-svelte/icons/clock';
    import { onDestroy, onMount } from 'svelte';
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

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/admin/dashboard' }];

    let pollInterval: ReturnType<typeof setInterval> | null = null;

    onMount(() => {
        pollInterval = setInterval(() => {
            router.reload({ only: ['kpis', 'overdueStages', 'nearDeadlineJobs'] });
        }, 30000);
    });

    onDestroy(() => {
        if (pollInterval) clearInterval(pollInterval);
    });
</script>

<AppHead title="Dashboard" />

<AdminLayout {breadcrumbs}>
    <!-- KPI row -->
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <StageCard title="Open Jobs" status="OPEN" description={`${kpis.openJobCards}`} />
        <StageCard title="Overdue Stages" status="OVERDUE" description={`${kpis.overdueStages}`} />
        <StageCard title="Pending Delays" status="IN_PROGRESS" description={`${kpis.pendingDelayReports}`} />
        <StageCard title="Near Deadline" status="IN_PROGRESS" description={`${kpis.nearDeadlineJobs}`} />
        <StageCard title="Completed" status="COMPLETED" description={`${kpis.completedJobCards}`} />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <!-- Overdue queue -->
        <section>
            <div class="mb-3 flex items-center gap-2">
                <AlertTriangle class="size-4 text-red-500" />
                <h2 class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Overdue stages</h2>
            </div>
            {#if overdueStages.length === 0}
                <div class="rounded-xl border border-dashed px-4 py-8 text-center">
                    <p class="text-sm text-muted-foreground">No overdue stages — all good.</p>
                </div>
            {:else}
                <div class="space-y-2">
                    {#each overdueStages as item (item.id)}
                        <div class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/50 dark:bg-red-950/20">
                            <div>
                                <p class="text-sm font-semibold text-foreground">{item.job_card.job_number}</p>
                                <p class="text-xs text-muted-foreground">{item.stage.name}</p>
                            </div>
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">Overdue</span>
                        </div>
                    {/each}
                </div>
            {/if}
        </section>

        <!-- Near deadline -->
        <section>
            <div class="mb-3 flex items-center gap-2">
                <Clock class="size-4 text-amber-500" />
                <h2 class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Due within 24h</h2>
            </div>
            {#if nearDeadlineJobs.length === 0}
                <div class="rounded-xl border border-dashed px-4 py-8 text-center">
                    <p class="text-sm text-muted-foreground">No jobs nearing deadline.</p>
                </div>
            {:else}
                <div class="space-y-2">
                    {#each nearDeadlineJobs as job (job.id)}
                        <div class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/20">
                            <div>
                                <p class="text-sm font-semibold text-foreground">{job.job_number}</p>
                                <p class="text-xs text-muted-foreground">{job.vehicle.registration_number}</p>
                            </div>
                            <p class="text-xs font-medium text-amber-700 dark:text-amber-300">
                                {new Date(job.promised_delivery_at).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                            </p>
                        </div>
                    {/each}
                </div>
            {/if}
        </section>
    </div>
</AdminLayout>
