<script lang="ts">
    import StatusBadge from '@/components/StatusBadge.svelte';

    type StageItem = {
        id: number;
        sequence: number;
        name: string;
        status: string;
        due_at?: string | null;
        planned_due_at?: string | null;
        planned_duration_value?: number | null;
        planned_duration_unit?: string | null;
        assigned_technician?: { name: string } | null;
        technicians?: Array<{ id: number; name: string }>;
    };

    let { stages }: { stages: StageItem[] } = $props();

    function dotClass(status: string): string {
        if (status === 'COMPLETED') return 'bg-green-500 ring-2 ring-green-200 dark:ring-green-900';
        if (status === 'IN_PROGRESS') return 'bg-blue-500 ring-4 ring-blue-100 dark:ring-blue-950';
        if (status === 'OVERDUE') return 'bg-red-500 ring-4 ring-red-100 dark:ring-red-950';
        if (status === 'BLOCKED') return 'bg-amber-500 ring-4 ring-amber-100 dark:ring-amber-950';
        return 'bg-border dark:bg-slate-600';
    }

    function rowClass(status: string): string {
        if (status === 'IN_PROGRESS') return 'bg-blue-50/60 dark:bg-blue-950/20 rounded-lg';
        if (status === 'OVERDUE') return 'bg-red-50/60 dark:bg-red-950/20 rounded-lg';
        if (status === 'BLOCKED') return 'bg-amber-50/60 dark:bg-amber-950/20 rounded-lg';
        return '';
    }
</script>

<ol class="relative space-y-0">
    {#each stages as stage, i (stage.id)}
        <li class="relative flex gap-4 {rowClass(stage.status)} {stage.status !== 'NOT_STARTED' && stage.status !== 'COMPLETED' ? 'px-3 py-2 -mx-3' : ''}">
            <div class="flex flex-col items-center">
                <span class="mt-1.5 h-3 w-3 shrink-0 rounded-full transition-all {dotClass(stage.status)}"></span>
                {#if i < stages.length - 1}
                    <span class="mt-1 w-px flex-1 bg-border"></span>
                {/if}
            </div>

            <div class="pb-6 min-w-0 {i === stages.length - 1 ? 'pb-0' : ''}">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-semibold">{stage.sequence}. {stage.name}</p>
                    <StatusBadge status={stage.status} />
                </div>
                <div class="mt-1 space-y-0.5 text-xs text-muted-foreground">
                    {#if (stage.technicians ?? []).length > 0}
                        <p>Assigned: {(stage.technicians ?? []).map((m) => m.name).join(', ')}</p>
                    {:else if stage.assigned_technician}
                        <p>Assigned: {stage.assigned_technician.name}</p>
                    {:else}
                        <p class="text-amber-600 dark:text-amber-400 font-medium">Unassigned</p>
                    {/if}
                    {#if stage.due_at}
                        <p>Due: {new Date(stage.due_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                    {/if}
                </div>
            </div>
        </li>
    {/each}
</ol>
