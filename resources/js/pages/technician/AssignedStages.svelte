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
    import { show } from '@/routes/technician/assigned-stages';
    import type { BreadcrumbItem } from '@/types';

    let { stages }: { stages: any[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Assigned Stages', href: '/technician/assigned-stages' }];

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
        <div class="rounded-xl border border-dashed px-4 py-16 text-center">
            <ClipboardList class="mx-auto size-10 text-muted-foreground/40" />
            <p class="mt-3 font-semibold">No stages assigned</p>
            <p class="mt-1 text-sm text-muted-foreground">Check back later for new work.</p>
        </div>
    {:else}
        {#if activeStages.length > 0}
            <div class="space-y-2">
                {#each activeStages as stage (stage.id)}
                    <a
                        href={show({ jobStage: stage.uuid }).url}
                        class="group flex items-center gap-4 rounded-xl border bg-card px-4 py-4 shadow-sm transition hover:shadow-md
                            {isOverdue(stage) ? 'border-l-4 border-l-red-500' : stage.status === 'BLOCKED' ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-transparent hover:border-l-primary'}"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold">{stage.stage.name}</p>
                                <StatusBadge status={stage.status} />
                            </div>
                            <p class="mt-0.5 text-sm text-muted-foreground">
                                Job <span class="font-medium text-foreground">#{stage.job_card.job_number}</span>
                                {#if stage.job_card?.vehicle}
                                    &nbsp;&mdash; {stage.job_card.vehicle.make} {stage.job_card.vehicle.model}
                                {/if}
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                                <span class="flex items-center gap-1 {isOverdue(stage) ? 'font-semibold text-red-600 dark:text-red-400' : 'text-muted-foreground'}">
                                    {#if isOverdue(stage)}
                                        <CircleAlert class="size-3.5" />
                                    {:else}
                                        <Clock3 class="size-3.5" />
                                    {/if}
                                    {dueLabel(stage)}
                                </span>
                                {#if isUrgent(stage)}
                                    <span class="rounded-full px-2 py-0.5 font-medium
                                        {isOverdue(stage) ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'}">
                                        {isOverdue(stage) ? 'Needs delay report' : 'Blocked — report required'}
                                    </span>
                                {/if}
                            </div>
                        </div>
                        <ChevronRight class="size-4 shrink-0 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" />
                    </a>
                {/each}
            </div>
        {/if}

        {#if completedStages.length > 0}
            <details class="mt-6 group">
                <summary class="flex cursor-pointer list-none items-center gap-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                    <ChevronRight class="size-3.5 transition group-open:rotate-90" />
                    Completed ({completedStages.length})
                </summary>
                <div class="mt-3 space-y-1.5">
                    {#each completedStages as stage (stage.id)}
                        <a
                            href={show({ jobStage: stage.uuid }).url}
                            class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-2.5 text-sm hover:bg-muted/50"
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
