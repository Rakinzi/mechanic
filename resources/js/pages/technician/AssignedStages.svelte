<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import CircleAlert from 'lucide-svelte/icons/circle-alert';
    import ClipboardList from 'lucide-svelte/icons/clipboard-list';
    import Clock3 from 'lucide-svelte/icons/clock-3';
    import { onDestroy, onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import TechnicianLayout from '@/layouts/TechnicianLayout.svelte';
    import { show } from '@/routes/mechanic/assigned-stages';
    import type { BreadcrumbItem } from '@/types';

    let { stages }: { stages: any[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Assigned Stages', href: '/mechanic/assigned-stages' }];

    let pollInterval: ReturnType<typeof setInterval> | null = null;

    onMount(() => {
        pollInterval = setInterval(() => {
            router.reload({
                only: ['stages'],
            });
        }, 30000);
    });

    onDestroy(() => {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    });

    const urgencyOrder = ['OVERDUE', 'IN_PROGRESS', 'BLOCKED', 'NOT_STARTED', 'COMPLETED'];
    const sortedStages = $derived(
        [...stages].sort((a, b) => {
            const ai = urgencyOrder.indexOf(a.status);
            const bi = urgencyOrder.indexOf(b.status);
            return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
        }),
    );

    const activeStages = $derived(sortedStages.filter((s) => s.status !== 'COMPLETED'));
    const completedStages = $derived(sortedStages.filter((s) => s.status === 'COMPLETED'));

    function isOverdue(stage: any): boolean {
        return stage.status === 'OVERDUE';
    }

    function isUrgent(stage: any): boolean {
        return stage.status === 'OVERDUE' || stage.status === 'BLOCKED';
    }

    function dueLabel(stage: any): string {
        if (!stage.due_at) return 'No due date';
        const due = new Date(stage.due_at);
        const now = new Date();
        const diffMs = due.getTime() - now.getTime();
        const diffH = Math.round(diffMs / 3_600_000);
        if (diffH < 0) return `Overdue by ${Math.abs(diffH)}h`;
        if (diffH < 24) return `Due in ${diffH}h`;
        return due.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }
</script>

<AppHead title="My Assigned Stages" />

<TechnicianLayout {breadcrumbs}>
    {#if stages.length === 0}
        <div class="rounded-xl border bg-white p-8 text-center shadow-sm dark:bg-slate-900">
            <ClipboardList class="mx-auto size-8 text-muted-foreground" />
            <p class="mt-2 font-medium">No stages assigned</p>
            <p class="text-sm text-muted-foreground">Check back later for new work.</p>
        </div>
    {:else}
        <!-- Active stages -->
        {#if activeStages.length > 0}
            <div class="space-y-3">
                {#each activeStages as stage (stage.id)}
                    <a
                        href={show({ jobStage: stage.uuid }).url}
                        class="block rounded-xl border bg-white p-4 shadow-sm transition hover:shadow-md dark:bg-slate-900
                            {isOverdue(stage) ? 'border-red-300 dark:border-red-700' : ''}
                            {stage.status === 'BLOCKED' ? 'border-amber-300 dark:border-amber-700' : ''}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{stage.stage.name}</p>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    Job <span class="font-medium text-foreground">#{stage.job_card.job_number}</span>
                                    {#if stage.job_card?.vehicle}
                                        &mdash; {stage.job_card.vehicle.make} {stage.job_card.vehicle.model}
                                    {/if}
                                </p>
                            </div>
                            <StatusBadge status={stage.status} />
                        </div>

                        <div class="mt-3 flex items-center gap-4 text-xs">
                            <span class="flex items-center gap-1 {isOverdue(stage) ? 'font-semibold text-red-600 dark:text-red-400' : 'text-muted-foreground'}">
                                {#if isOverdue(stage)}
                                    <CircleAlert class="size-3.5" />
                                {:else}
                                    <Clock3 class="size-3.5" />
                                {/if}
                                {dueLabel(stage)}
                            </span>
                            {#if isUrgent(stage)}
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    {isOverdue(stage) ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'}">
                                    {isOverdue(stage) ? 'Needs delay report' : 'Blocked — report required'}
                                </span>
                            {/if}
                            <span class="ml-auto flex items-center gap-1 font-medium text-primary">
                                <span>Open</span>
                                <ChevronRight class="size-3.5" />
                            </span>
                        </div>
                    </a>
                {/each}
            </div>
        {/if}

        <!-- Completed stages (collapsed) -->
        {#if completedStages.length > 0}
            <details class="mt-6 group">
                <summary class="flex cursor-pointer items-center gap-2 text-sm font-medium text-muted-foreground list-none">
                    <ChevronRight class="size-4 transition group-open:rotate-90" />
                    Completed ({completedStages.length})
                </summary>
                <div class="mt-3 space-y-2">
                    {#each completedStages as stage (stage.id)}
                        <a
                            href={show({ jobStage: stage.uuid }).url}
                            class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3 text-sm hover:bg-muted/50"
                        >
                            <span class="text-muted-foreground">
                                {stage.stage.name} &mdash; #{stage.job_card.job_number}
                            </span>
                            <StatusBadge status={stage.status} />
                        </a>
                    {/each}
                </div>
            </details>
        {/if}
    {/if}
</TechnicianLayout>
