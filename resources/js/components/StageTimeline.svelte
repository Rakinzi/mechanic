<script lang="ts">
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
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
        assigned_mechanic?: { name: string } | null;
    };

    let { stages }: { stages: StageItem[] } = $props();
</script>

<Accordion multiple>
    {#each stages as stage (stage.id)}
        <Accordion.Item value={String(stage.id)} class="rounded-lg border p-3">
            <Accordion.ItemTrigger class="mb-1 flex w-full items-center justify-between gap-2 text-left">
                <p class="text-sm font-semibold">{stage.sequence}. {stage.name}</p>
                <StatusBadge status={stage.status} />
            </Accordion.ItemTrigger>
            <Accordion.ItemContent class="text-xs text-muted-foreground">
                <p>Mechanic: {stage.assigned_mechanic?.name ?? 'Unassigned'}</p>
                <p>Planned time: {stage.planned_duration_value ?? 'N/A'} {stage.planned_duration_unit ?? ''}</p>
                <p>Planned due: {stage.planned_due_at ? new Date(stage.planned_due_at).toLocaleString() : 'N/A'}</p>
                <p>Due: {stage.due_at ? new Date(stage.due_at).toLocaleString() : 'N/A'}</p>
            </Accordion.ItemContent>
        </Accordion.Item>
    {/each}
</Accordion>
