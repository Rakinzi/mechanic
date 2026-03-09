<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DelayReportModal from '@/components/DelayReportModal.svelte';
    import PhotoGallery from '@/components/PhotoGallery.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import MechanicLayout from '@/layouts/MechanicLayout.svelte';
    import { block, complete, pause, start } from '@/routes/mechanic/job-stages';
    import type { BreadcrumbItem } from '@/types';

    let { jobStage }: { jobStage: any } = $props();

    let showDelayModal = $state(false);

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        { title: 'Assigned Stages', href: '/mechanic/assigned-stages' },
        { title: `Stage #${jobStage.id}`, href: `/mechanic/assigned-stages/${jobStage.id}` },
    ]);

    const isOverdue = $derived(jobStage.status === 'OVERDUE');
    const isBlocked = $derived(jobStage.status === 'BLOCKED');
    const hasApprovedDelayReport = $derived(
        (jobStage.delay_reports ?? []).some((report: { status: string }) => report.status === 'APPROVED'),
    );
    const canComplete = $derived(!isOverdue || hasApprovedDelayReport);

    $effect(() => {
        if (isOverdue) {
            showDelayModal = true;
        }
    });
</script>

<AppHead title="Stage Execution" />

<MechanicLayout {breadcrumbs}>
    <section class="space-y-3 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">{jobStage.stage.name}</h2>
            <StatusBadge status={jobStage.status} />
        </div>
        <p class="text-sm text-muted-foreground">Job #{jobStage.job_card.job_number}</p>
        <p class="text-sm text-muted-foreground">Planned due {jobStage.planned_due_at ? new Date(jobStage.planned_due_at).toLocaleString() : 'N/A'}</p>
        <p class="text-sm text-muted-foreground">Due {jobStage.due_at ? new Date(jobStage.due_at).toLocaleString() : 'N/A'}</p>
        {#if isOverdue && !hasApprovedDelayReport}
            <p class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                This stage is overdue. Submit and get a delay report approved before completion.
            </p>
        {/if}
        {#if isBlocked && !isOverdue}
            <p class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                This stage is blocked. Submit a delay report with an updated ETA so admin can review it.
            </p>
        {/if}

        <div class="flex flex-wrap gap-2">
            <Form {...start.form({ jobStage: jobStage.id })}><button type="submit" class="btn preset-filled">Start</button></Form>
            <Form {...pause.form({ jobStage: jobStage.id })}><button type="submit" class="btn preset-tonal">Pause</button></Form>
            <Form {...block.form({ jobStage: jobStage.id })}><button type="submit" class="btn preset-tonal">Block</button></Form>
            <Form {...complete.form({ jobStage: jobStage.id })}>
                <button type="submit" class="btn preset-tonal" disabled={!canComplete}>Complete</button>
            </Form>
            {#if isOverdue || isBlocked || jobStage.status === 'IN_PROGRESS'}
                <button type="button" class="btn bg-red-600 text-white" onclick={() => (showDelayModal = true)}>Submit Delay Report</button>
            {/if}
        </div>
    </section>

    <section class="mt-6 space-y-3">
        <h3 class="text-base font-semibold">Stage media</h3>
        <PhotoGallery media={jobStage.media ?? []} />
    </section>

    <section class="mt-6 space-y-3">
        <h3 class="text-base font-semibold">Logs</h3>
        <Accordion multiple>
            {#each jobStage.logs as log (log.id)}
                <Accordion.Item value={String(log.id)} class="rounded-md border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between text-left">
                        <span class="font-medium">{log.action}</span>
                        <span class="text-xs text-muted-foreground">{new Date(log.happened_at).toLocaleString()}</span>
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="pt-2 text-sm text-muted-foreground">
                        {#if log.actor}
                            <p>Actor: {log.actor.name}</p>
                        {/if}
                        {#if log.from_status || log.to_status}
                            <p>Transition: {log.from_status ?? 'N/A'} -> {log.to_status ?? 'N/A'}</p>
                        {/if}
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>

    <DelayReportModal jobStageId={jobStage.id} bind:isOpen={showDelayModal} />
</MechanicLayout>
