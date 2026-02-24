<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import { onDestroy, onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import MechanicLayout from '@/layouts/MechanicLayout.svelte';
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
</script>

<AppHead title="My Assigned Stages" />

<MechanicLayout {breadcrumbs}>
    <section class="space-y-3">
        <Accordion multiple>
            {#each stages as stage (stage.id)}
                <Accordion.Item value={String(stage.id)} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between text-left">
                        <span class="font-medium">{stage.job_card.job_number} - {stage.stage.name}</span>
                        <StatusBadge status={stage.status} />
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="space-y-1 text-sm text-muted-foreground">
                        <p>Due: {stage.due_at ? new Date(stage.due_at).toLocaleString() : 'N/A'}</p>
                        <a href={show({ jobStage: stage.id }).url} class="underline">Open stage work</a>
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>
</MechanicLayout>
