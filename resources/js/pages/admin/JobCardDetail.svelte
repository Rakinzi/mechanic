<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PhotoGallery from '@/components/PhotoGallery.svelte';
    import StageTimeline from '@/components/StageTimeline.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import { approve, reject } from '@/routes/admin/delay-reports';
    import { close } from '@/routes/admin/job-cards';
    import type { BreadcrumbItem } from '@/types';

    let {
        jobCard,
        technicians,
        admins,
        release_stage_id,
        summaryUrl,
    }: {
        jobCard: any;
        technicians: Array<{ id: number; name: string }>;
        admins: Array<{ id: number; name: string }>;
        release_stage_id: number | null;
        summaryUrl: string;
    } = $props();

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        { title: 'Job Cards', href: '/admin/job-cards' },
        { title: jobCard.job_number, href: `/admin/job-cards/${jobCard.uuid}` },
    ]);
</script>

<AppHead title={`Job ${jobCard.job_number}`} />

<AdminLayout {breadcrumbs}>
    <section class="space-y-2 rounded-lg border p-4">
        <h2 class="text-lg font-semibold">{jobCard.job_number}</h2>
        <p class="text-sm text-muted-foreground">Vehicle: {jobCard.vehicle.make} {jobCard.vehicle.model} ({jobCard.vehicle.registration_number})</p>
        <p class="text-sm text-muted-foreground">Client: {jobCard.vehicle.client.name}</p>
        <p class="text-sm text-muted-foreground">Current stage: {jobCard.current_job_stage?.stage?.name ?? 'Completed'}</p>
        <p class="text-sm">Complaint: {jobCard.customer_complaint}</p>
        <div class="flex flex-wrap items-center gap-2">
            <a class="btn preset-tonal" href={summaryUrl} target="_blank" rel="noopener noreferrer">Open summary</a>
            <a class="btn preset-tonal" href={`${summaryUrl}?download=1`} target="_blank" rel="noopener noreferrer">Download PDF</a>
            {#if jobCard.status !== 'COMPLETED'}
                <Form {...close.form({ jobCard: jobCard.uuid })}>
                    <button type="submit" class="btn preset-tonal">Close job card</button>
                </Form>
            {/if}
        </div>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-[220px_1fr]">
        <aside class="h-fit rounded-lg border p-3">
            <p class="mb-2 text-sm font-semibold">Sections</p>
            <nav class="flex flex-col gap-2 text-sm">
                <a href="#timeline" class="rounded px-2 py-1 hover:bg-muted">Stage Timeline</a>
                <a href="#delays" class="rounded px-2 py-1 hover:bg-muted">Delay Reports</a>
                <a href="#audit-trail" class="rounded px-2 py-1 hover:bg-muted">Audit Trail</a>
            </nav>
        </aside>

        <div class="space-y-6">
            <div id="timeline" class="scroll-mt-24 space-y-3">
                <h3 class="text-base font-semibold">Stage Timeline</h3>
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

            <div class="space-y-3">
                <h3 class="text-base font-semibold">Future stage planning</h3>
                {#each jobCard.job_stages.filter((stage: any) => stage.status === 'NOT_STARTED') as stage (stage.id)}
                    <Form action={`/admin/job-cards/${jobCard.uuid}/stages/${stage.uuid}`} method="post" class="grid gap-3 rounded-lg border p-3 md:grid-cols-4">
                        <input type="hidden" name="_method" value="PATCH" />
                        <div>
                            <p class="text-sm font-medium">{stage.stage.name}</p>
                            <p class="text-xs text-muted-foreground">Sequence {stage.sequence}</p>
                        </div>
                        <label class="space-y-1 text-sm">
                            <span>{stage.stage_id === release_stage_id ? 'Admin' : 'Technicians'}</span>
                            <select name="technician_ids[]" multiple class="w-full rounded-md border px-3 py-2 min-h-[80px]">
                                {#each (stage.stage_id === release_stage_id ? admins : technicians) as person (person.id)}
                                    <option
                                        value={person.id}
                                        selected={(stage.technicians ?? []).some((m: any) => m.id === person.id)}
                                    >{person.name}</option>
                                {/each}
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
                <h3 class="text-base font-semibold">Delay Reports</h3>
                {#each jobCard.job_stages as stage (stage.id)}
                    {#each stage.delay_reports as report (report.id)}
                        <div class="rounded-lg border p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium">{stage.stage.name}</p>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                    {report.status === 'APPROVED' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' :
                                     report.status === 'REJECTED' ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' :
                                     'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'}">
                                    {report.status}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground">Reason: {report.reason_category}</p>
                            <p class="text-sm">{report.explanation}</p>
                            <p class="text-xs text-muted-foreground">Proposed ETA: {report.proposed_eta ? new Date(report.proposed_eta).toLocaleString() : 'N/A'}</p>
                            <p class="text-xs text-muted-foreground">Submitted by: {report.submitter?.name ?? 'N/A'}</p>
                            {#if (report.media ?? []).length > 0}
                                <div class="mt-2">
                                    <p class="text-xs font-medium mb-1">Attachments</p>
                                    <PhotoGallery media={report.media} />
                                </div>
                            {/if}
                            {#if report.status === 'PENDING'}
                                <div class="mt-2 flex gap-2">
                                    <Form {...approve.form({ delayReport: report.id })}>
                                        <button type="submit" class="btn preset-filled">Approve</button>
                                    </Form>
                                    <Form {...reject.form({ delayReport: report.id })}>
                                        <button type="submit" class="btn bg-red-600 text-white">Reject</button>
                                    </Form>
                                </div>
                            {:else if report.review_comment}
                                <p class="text-xs text-muted-foreground">Review comment: {report.review_comment}</p>
                            {/if}
                        </div>
                    {/each}
                {/each}
            </div>

            <div id="audit-trail" class="scroll-mt-24 space-y-3">
                <h3 class="text-base font-semibold">Audit Trail</h3>
                {#each jobCard.audits as audit (audit.id)}
                    <div class="rounded-lg border p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium">{audit.description}</p>
                            <p class="text-xs text-muted-foreground">{new Date(audit.happened_at).toLocaleString()}</p>
                        </div>
                        <div class="mt-1 text-sm text-muted-foreground">
                            <p>Event: {audit.event}</p>
                            <p>Actor: {audit.actor?.name ?? 'System'}</p>
                            {#if audit.job_stage}
                                <p>Stage: {audit.job_stage.stage.name}</p>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    </section>
</AdminLayout>
