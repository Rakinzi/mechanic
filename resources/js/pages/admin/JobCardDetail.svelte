<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PhotoGallery from '@/components/PhotoGallery.svelte';
    import StageTimeline from '@/components/StageTimeline.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import { approve, reject } from '@/routes/admin/delay-reports';
    import { close } from '@/routes/admin/job-cards';
    import type { BreadcrumbItem } from '@/types';

    let {
        jobCard,
        technicians,
        admins,
        summaryUrl,
    }: {
        jobCard: any;
        technicians: Array<{ id: number; name: string }>;
        admins: Array<{ id: number; name: string }>;
        summaryUrl: string;
    } = $props();

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        { title: 'Job Cards', href: '/admin/job-cards' },
        { title: jobCard.job_number, href: `/admin/job-cards/${jobCard.uuid}` },
    ]);
</script>

<AppHead title={`Job ${jobCard.job_number}`} />

<AdminLayout {breadcrumbs}>
    <!-- Job header -->
    <section class="rounded-xl border bg-card p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Job Card</p>
                <h1 class="mt-0.5 text-2xl font-black tracking-tight">{jobCard.job_number}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                    <span class="flex items-center gap-1.5">
                        <span class="rounded border border-slate-400 bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-bold tracking-widest text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{jobCard.vehicle.registration_number}</span>
                        <span class="font-medium text-foreground">{jobCard.vehicle.make} {jobCard.vehicle.model}</span>
                    </span>
                    <span>{jobCard.vehicle.client.name}</span>
                    <span>Stage: <span class="font-medium text-foreground">{jobCard.current_job_stage?.stage?.name ?? 'Completed'}</span></span>
                </div>
                {#if jobCard.customer_complaint}
                    <p class="mt-3 max-w-prose text-sm text-foreground">{jobCard.customer_complaint}</p>
                {/if}
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a class="btn preset-tonal" href={summaryUrl} target="_blank" rel="noopener noreferrer">Summary</a>
                <a class="btn preset-tonal" href={`${summaryUrl}?download=1`} target="_blank" rel="noopener noreferrer">Download PDF</a>
                {#if jobCard.status !== 'COMPLETED'}
                    <Form {...close.form({ jobCard: jobCard.uuid })}>
                        <button type="submit" class="btn preset-tonal">Close job card</button>
                    </Form>
                {/if}
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 md:grid-cols-[200px_1fr]">
        <aside class="h-fit rounded-xl border bg-card p-3 shadow-sm">
            <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Jump to</p>
            <nav class="flex flex-col gap-0.5 text-sm">
                <a href="#timeline" class="rounded-lg px-2 py-1.5 font-medium hover:bg-muted">Stage Timeline</a>
                <a href="#planning" class="rounded-lg px-2 py-1.5 font-medium hover:bg-muted">Stage Planning</a>
                <a href="#delays" class="rounded-lg px-2 py-1.5 font-medium hover:bg-muted">Delay Reports</a>
                <a href="#audit-trail" class="rounded-lg px-2 py-1.5 font-medium hover:bg-muted">Audit Trail</a>
            </nav>
        </aside>

        <div class="space-y-6">
            <div id="timeline" class="scroll-mt-24 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Stage Timeline</h3>
                <StageTimeline
                    stages={jobCard.job_stages.map((stage: any) => ({
                        id: stage.id,
                        sequence: stage.sequence,
                        name: stage.stage.name,
                        status: stage.status,
                        due_at: stage.due_at,
                        planned_due_at: stage.planned_due_at,
                        planned_duration_value: stage.planned_duration_value,
                        planned_duration_unit: stage.planned_duration_unit,
                        assigned_technician: stage.assigned_technician,
                        technicians: stage.technicians ?? [],
                    }))}
                />
            </div>

            <div id="planning" class="scroll-mt-24 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Stage Planning</h3>
                {#if jobCard.job_stages.filter((stage: any) => stage.status === 'NOT_STARTED').length === 0}
                    <p class="text-sm text-muted-foreground">No upcoming stages to plan.</p>
                {/if}
                {#each jobCard.job_stages.filter((stage: any) => stage.status === 'NOT_STARTED') as stage (stage.id)}
                    <Form action={`/admin/job-cards/${jobCard.uuid}/stages/${stage.uuid}`} method="post" class="grid gap-3 rounded-xl border bg-card p-4 shadow-sm md:grid-cols-4">
                        <input type="hidden" name="_method" value="PATCH" />
                        <div>
                            <p class="text-sm font-medium">{stage.stage.name}</p>
                            <p class="text-xs text-muted-foreground">Sequence {stage.sequence}</p>
                        </div>
                        <label class="space-y-1 text-sm">
                            <span>Technicians &amp; Admins</span>
                            <select name="technician_ids[]" multiple class="w-full rounded-md border px-3 py-2 min-h-[80px]">
                                {#if technicians.length > 0}
                                    <optgroup label="Technicians">
                                        {#each technicians as person (person.id)}
                                            <option
                                                value={person.id}
                                                selected={(stage.technicians ?? []).some((m: any) => m.id === person.id)}
                                            >{person.name}</option>
                                        {/each}
                                    </optgroup>
                                {/if}
                                {#if admins.length > 0}
                                    <optgroup label="Admins">
                                        {#each admins as person (person.id)}
                                            <option
                                                value={person.id}
                                                selected={(stage.technicians ?? []).some((m: any) => m.id === person.id)}
                                            >{person.name}</option>
                                        {/each}
                                    </optgroup>
                                {/if}
                            </select>
                            <span class="text-xs text-muted-foreground">Hold Ctrl/Cmd to select multiple</span>
                        </label>
                        <label class="space-y-1 text-sm">
                            <span>Duration</span>
                            <input type="number" min="1" name="planned_duration_value" class="w-full rounded-md border px-3 py-2" value={stage.planned_duration_value ?? 1} />
                        </label>
                        <label class="space-y-1 text-sm">
                            <span>Unit</span>
                            <select name="planned_duration_unit" class="w-full rounded-md border px-3 py-2">
                                <option value="hours" selected={stage.planned_duration_unit === 'hours'}>Hours</option>
                                <option value="days" selected={stage.planned_duration_unit === 'days'}>Days</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-sm md:col-span-3">
                            <span>Admin note</span>
                            <textarea name="latest_note" class="min-h-20 w-full rounded-md border px-3 py-2">{stage.latest_note ?? ''}</textarea>
                        </label>
                        <div class="md:col-span-1 flex items-end">
                            <button type="submit" class="btn preset-filled w-full">Update future stage</button>
                        </div>
                    </Form>
                {/each}
            </div>

            <div id="delays" class="scroll-mt-24 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Delay Reports</h3>
                {#each jobCard.job_stages as stage (stage.id)}
                    {#each stage.delay_reports as report (report.id)}
                        <div class="rounded-xl border bg-card p-4 shadow-sm space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold">{stage.stage.name}</p>
                                <StatusBadge status={report.status} />
                            </div>
                            <div class="grid gap-1 text-sm">
                                <p><span class="text-muted-foreground">Reason:</span> {report.reason_category.replaceAll('_', ' ')}</p>
                                <p>{report.explanation}</p>
                                <p class="text-xs text-muted-foreground">
                                    ETA: {report.proposed_eta ? new Date(report.proposed_eta).toLocaleString() : 'N/A'}
                                    &nbsp;&bull;&nbsp; By: {report.submitter?.name ?? 'N/A'}
                                </p>
                            </div>
                            {#if (report.media ?? []).length > 0}
                                <div>
                                    <p class="mb-1 text-xs font-medium text-muted-foreground">Attachments</p>
                                    <PhotoGallery media={report.media} />
                                </div>
                            {/if}
                            {#if report.status === 'PENDING'}
                                <div class="flex gap-2 border-t pt-3">
                                    <Form {...approve.form({ delayReport: report.id })}>
                                        <button type="submit" class="btn preset-filled">Approve</button>
                                    </Form>
                                    <Form {...reject.form({ delayReport: report.id })}>
                                        <button type="submit" class="btn bg-destructive text-destructive-foreground">Reject</button>
                                    </Form>
                                </div>
                            {:else if report.review_comment}
                                <p class="border-t pt-3 text-xs text-muted-foreground">Review: {report.review_comment}</p>
                            {/if}
                        </div>
                    {/each}
                {/each}
            </div>

            <div id="audit-trail" class="scroll-mt-24 space-y-2">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Audit Trail</h3>
                {#each jobCard.audits as audit (audit.id)}
                    <div class="flex items-start justify-between gap-4 rounded-xl border bg-card px-4 py-3 shadow-sm">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">{audit.description}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {audit.actor?.name ?? 'System'}
                                {#if audit.job_stage}
                                    &bull; {audit.job_stage.stage.name}
                                {/if}
                            </p>
                        </div>
                        <p class="shrink-0 text-xs text-muted-foreground">{new Date(audit.happened_at).toLocaleString()}</p>
                    </div>
                {/each}
            </div>
        </div>
    </section>
</AdminLayout>
