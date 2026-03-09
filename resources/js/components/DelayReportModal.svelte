<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Dialog, FileUpload } from '@skeletonlabs/skeleton-svelte';
    import InputError from '@/components/InputError.svelte';
    import { store as storeDelayReport } from '@/routes/mechanic/delay-reports';

    let {
        jobStageId,
        isOpen = $bindable(false),
    }: {
        jobStageId: number;
        isOpen?: boolean;
    } = $props();
</script>

<Dialog
    open={isOpen}
    onOpenChange={(details) => {
        isOpen = details.open;
    }}
>
    <Dialog.Backdrop class="fixed inset-0 z-40 bg-black/40" />
    <Dialog.Positioner class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <Dialog.Content class="w-full max-w-xl rounded-lg border bg-white p-5 shadow-lg dark:bg-slate-900">
            <Dialog.Title class="text-lg font-semibold">Submit delay report</Dialog.Title>
            <Dialog.Description class="mb-4 text-sm text-muted-foreground">
                Use this when the stage is blocked, delayed, or overdue so admin can review the revised ETA.
            </Dialog.Description>

            <Form {...storeDelayReport.form({ jobStage: jobStageId })} class="space-y-4">
                {#snippet children({ errors, processing })}
                    <div class="space-y-2">
                        <label for="reason_category" class="text-sm font-medium">Reason category</label>
                        <select
                            id="reason_category"
                            name="reason_category"
                            class="w-full rounded-md border px-3 py-2"
                            required
                        >
                            <option value="PARTS_DELAY">Parts delay</option>
                            <option value="EXTERNAL_VENDOR">External vendor</option>
                            <option value="REWORK">Rework</option>
                            <option value="CLIENT_APPROVAL">Client approval</option>
                            <option value="ADDITIONAL_DAMAGE">Additional damage</option>
                            <option value="OTHER">Other</option>
                        </select>
                        <InputError message={errors.reason_category} />
                    </div>

                    <div class="space-y-2">
                        <label for="explanation" class="text-sm font-medium">Explanation</label>
                        <textarea id="explanation" name="explanation" class="min-h-24 w-full rounded-md border px-3 py-2" required></textarea>
                        <InputError message={errors.explanation} />
                    </div>

                    <div class="space-y-2">
                        <label for="proposed_eta" class="text-sm font-medium">Proposed ETA</label>
                        <input id="proposed_eta" type="datetime-local" name="proposed_eta" class="w-full rounded-md border px-3 py-2" required />
                        <InputError message={errors.proposed_eta} />
                    </div>

                    <div class="space-y-2">
                        <FileUpload allowDrop name="attachments" maxFiles={5}>
                            <FileUpload.Label class="text-sm font-medium">Attachments</FileUpload.Label>
                            <FileUpload.Dropzone class="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                                Drag files here or
                                <FileUpload.Trigger class="ml-1 underline">browse</FileUpload.Trigger>
                                <FileUpload.HiddenInput name="attachments[]" multiple />
                            </FileUpload.Dropzone>
                        </FileUpload>
                        <InputError message={errors.attachments} />
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn preset-filled" disabled={processing}>Submit delay report</button>
                        <Dialog.CloseTrigger class="btn preset-tonal">Cancel</Dialog.CloseTrigger>
                    </div>
                {/snippet}
            </Form>
        </Dialog.Content>
    </Dialog.Positioner>
</Dialog>
