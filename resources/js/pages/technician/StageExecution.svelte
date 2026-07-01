<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import Check from 'lucide-svelte/icons/check';
    import CircleAlert from 'lucide-svelte/icons/circle-alert';
    import ClipboardList from 'lucide-svelte/icons/clipboard-list';
    import Lock from 'lucide-svelte/icons/lock';
    import Pause from 'lucide-svelte/icons/pause';
    import Play from 'lucide-svelte/icons/play';
    import AppHead from '@/components/AppHead.svelte';
    import AlertError from '@/components/AlertError.svelte';
    import DelayReportModal from '@/components/DelayReportModal.svelte';
    import PhotoGallery from '@/components/PhotoGallery.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import TechnicianLayout from '@/layouts/TechnicianLayout.svelte';
    import { block, complete, pause, start } from '@/routes/technician/job-stages';
    import type { BreadcrumbItem } from '@/types';

    let { jobStage }: { jobStage: any } = $props();

    let showDelayModal = $state(false);

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        { title: 'Assigned Stages', href: '/technician/assigned-stages' },
        { title: `${jobStage.stage.name} — ${jobStage.job_card.job_number}`, href: `/technician/assigned-stages/${jobStage.uuid}` },
    ]);

    const isNotStarted = $derived(jobStage.status === 'NOT_STARTED');
    const isInProgress = $derived(jobStage.status === 'IN_PROGRESS');
    const isOverdue = $derived(jobStage.status === 'OVERDUE');
    const isBlocked = $derived(jobStage.status === 'BLOCKED');
    const isCompleted = $derived(jobStage.status === 'COMPLETED');
    const hasApprovedDelayReport = $derived(
        (jobStage.delay_reports ?? []).some((report: { status: string }) => report.status === 'APPROVED'),
    );

    const canStart = $derived(isNotStarted || isBlocked);
    const canPause = $derived(isInProgress || isOverdue);
    const canBlock = $derived(isInProgress || isOverdue);
    const canComplete = $derived((isInProgress || isOverdue) && (!isOverdue || hasApprovedDelayReport));
    const stageErrors = $derived.by(() => {
        const errors = ($page.props.errors ?? {}) as Record<string, string | string[] | undefined>;
        const stageError = errors.stage;

        if (Array.isArray(stageError)) {
            return stageError.filter(Boolean);
        }

        return stageError ? [stageError] : [];
    });

    $effect(() => {
        if (isOverdue) {
            showDelayModal = true;
        }
    });
</script>

<AppHead title="Stage Execution" />

<TechnicianLayout {breadcrumbs}>
    <!-- Stage header card -->
    <div class="rounded-xl border bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">{jobStage.stage.name}</h2>
                <p class="mt-0.5 text-sm text-muted-foreground">Job <span class="font-medium text-foreground">#{jobStage.job_card.job_number}</span></p>
            </div>
            <StatusBadge status={jobStage.status} />
        </div>

        <!-- Meta info grid -->
        <div class="mt-4 grid grid-cols-2 gap-3 text-sm md:grid-cols-3">
            <div class="rounded-lg bg-muted/40 px-3 py-2">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Planned due</p>
                <p class="mt-0.5 font-medium">{jobStage.planned_due_at ? new Date(jobStage.planned_due_at).toLocaleString() : '—'}</p>
            </div>
            <div class="rounded-lg px-3 py-2 {isOverdue ? 'bg-red-50 dark:bg-red-950/30' : 'bg-muted/40'}">
                <p class="text-xs font-medium uppercase tracking-wide {isOverdue ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground'}">Due</p>
                <p class="mt-0.5 font-medium {isOverdue ? 'text-red-700 dark:text-red-300' : ''}">{jobStage.due_at ? new Date(jobStage.due_at).toLocaleString() : '—'}</p>
            </div>
            {#if jobStage.job_card?.vehicle}
                <div class="rounded-lg bg-muted/40 px-3 py-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Vehicle</p>
                    <p class="mt-0.5 font-medium">{jobStage.job_card.vehicle.make} {jobStage.job_card.vehicle.model}</p>
                </div>
            {/if}
        </div>

        <!-- Alert banners -->
        {#if isOverdue && !hasApprovedDelayReport}
            <div class="mt-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                <CircleAlert class="mt-0.5 size-4 shrink-0" />
                <p>This stage is <strong>overdue</strong>. Submit a delay report and have admin approve it before you can complete this stage.</p>
            </div>
        {/if}
        {#if isBlocked && !isOverdue}
            <div class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                <Lock class="mt-0.5 size-4 shrink-0" />
                <p>This stage is <strong>blocked</strong>. Submit a delay report with an updated ETA so admin can review it.</p>
            </div>
        {/if}
        {#if stageErrors.length > 0}
            <div class="mt-4">
                <AlertError errors={stageErrors} title="Stage action could not be completed." />
            </div>
        {/if}

        <!-- Action buttons -->
        {#if !isCompleted}
            <div class="mt-5 flex flex-wrap gap-2 border-t pt-4">
                {#if canStart}
                    <Form {...start.form({ jobStage: jobStage.uuid })}>
                        <button type="submit" class="btn preset-filled gap-1.5">
                            <Play class="size-4" />
                            Start
                        </button>
                    </Form>
                {/if}
                {#if canPause}
                    <Form {...pause.form({ jobStage: jobStage.uuid })}>
                        <button type="submit" class="btn preset-tonal gap-1.5">
                            <Pause class="size-4" />
                            Pause
                        </button>
                    </Form>
                {/if}
                {#if canBlock}
                    <Form {...block.form({ jobStage: jobStage.uuid })}>
                        <button type="submit" class="btn preset-tonal gap-1.5">
                            <Lock class="size-4" />
                            Block
                        </button>
                    </Form>
                {/if}
                <Form {...complete.form({ jobStage: jobStage.uuid })}>
                    <button type="submit" class="btn gap-1.5 {canComplete ? 'preset-tonal' : 'opacity-40 cursor-not-allowed bg-muted text-muted-foreground'}" disabled={!canComplete}>
                        <Check class="size-4" />
                        Complete
                    </button>
                </Form>
                {#if isOverdue || isBlocked || isInProgress}
                    <button
                        type="button"
                        class="btn ml-auto gap-1.5 border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-950/30 dark:text-red-300"
                        onclick={() => (showDelayModal = true)}
                    >
                        <ClipboardList class="size-4" />
                        Submit Delay Report
                    </button>
                {/if}
            </div>
        {:else}
            <div class="mt-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                <Check class="size-4 shrink-0" />
                <span>This stage has been completed.</span>
            </div>
        {/if}
    </div>

    <!-- Stage media -->
    <div class="mt-5 rounded-xl border bg-card p-5 shadow-sm">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Stage photos</h3>
        {#if (jobStage.media ?? []).length > 0}
            <PhotoGallery media={jobStage.media ?? []} />
        {:else}
            <p class="mb-4 text-sm text-muted-foreground">No photos uploaded yet.</p>
        {/if}
        <Form
            action={`/technician/job-stages/${jobStage.uuid}/media`}
            method="post"
            enctype="multipart/form-data"
            class="mt-4 border-t pt-4"
            let:processing
            let:errors
        >
            <label class="block space-y-1.5">
                <span class="text-sm font-medium">Upload photos</span>
                <input
                    type="file"
                    name="photos[]"
                    multiple
                    accept="image/jpeg,image/png,image/webp,image/heic"
                    class="block w-full rounded-md border bg-background px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-primary file:px-3 file:py-1 file:text-sm file:font-medium file:text-primary-foreground"
                />
                <p class="text-xs text-muted-foreground">Up to 10 photos &bull; JPG, PNG, WebP, HEIC &bull; Max 10 MB each</p>
            </label>
            {#if errors.photos}
                <p class="mt-1 text-xs text-red-600">{errors.photos}</p>
            {/if}
            <button type="submit" disabled={processing} class="btn preset-filled mt-3 text-sm">
                {processing ? 'Uploading…' : 'Upload photos'}
            </button>
        </Form>
    </div>

    <!-- Logs -->
    {#if (jobStage.logs ?? []).length > 0}
        <div class="mt-5 rounded-xl border bg-card p-5 shadow-sm">
            <h3 class="mb-3 text-base font-semibold">Activity log</h3>
            <Accordion multiple>
                {#each jobStage.logs as log (log.id)}
                    <Accordion.Item value={String(log.id)} class="rounded-lg border px-3 py-2">
                        <Accordion.ItemTrigger class="flex w-full items-center justify-between text-left">
                            <span class="text-sm font-medium capitalize">{log.action.replace(/_/g, ' ').toLowerCase()}</span>
                            <span class="text-xs text-muted-foreground">{new Date(log.happened_at).toLocaleString()}</span>
                        </Accordion.ItemTrigger>
                        <Accordion.ItemContent class="pt-2 text-sm text-muted-foreground">
                            {#if log.actor}
                                <p>By: {log.actor.name}</p>
                            {/if}
                            {#if log.from_status || log.to_status}
                                <p class="mt-0.5">
                                    {log.from_status?.replace(/_/g, ' ') ?? '—'}
                                    <span class="mx-1 text-muted-foreground">→</span>
                                    {log.to_status?.replace(/_/g, ' ') ?? '—'}
                                </p>
                            {/if}
                        </Accordion.ItemContent>
                    </Accordion.Item>
                {/each}
            </Accordion>
        </div>
    {/if}

    <DelayReportModal jobStageId={jobStage.uuid} bind:isOpen={showDelayModal} />
</TechnicianLayout>
