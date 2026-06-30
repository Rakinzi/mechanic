<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Dialog } from '@skeletonlabs/skeleton-svelte';
    import Paperclip from 'lucide-svelte/icons/paperclip';
    import Upload from 'lucide-svelte/icons/upload';
    import X from 'lucide-svelte/icons/x';
    import InputError from '@/components/InputError.svelte';
    import { store as storeDelayReport } from '@/routes/technician/delay-reports';

    let {
        jobStageId,
        isOpen = $bindable(false),
    }: {
        jobStageId: string;
        isOpen?: boolean;
    } = $props();

    const route = $derived(storeDelayReport({ jobStage: jobStageId }));

    const form = useForm({
        reason_category: 'PARTS_DELAY',
        explanation: '',
        proposed_eta: '',
        attachments: [] as File[],
    });

    let dragging = $state(false);
    let fileInput: HTMLInputElement;

    function addFiles(incoming: FileList | null) {
        if (!incoming) return;
        const combined = [...$form.attachments, ...Array.from(incoming)].slice(0, 5);
        $form.attachments = combined;
    }

    function removeFile(index: number) {
        $form.attachments = $form.attachments.filter((_, i) => i !== index);
    }

    function formatSize(bytes: number) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function submit(e: Event) {
        e.preventDefault();
        $form.post(route.url, {
            forceFormData: true,
            onSuccess: () => {
                isOpen = false;
                $form.reset();
            },
        });
    }
</script>

<Dialog
    open={isOpen}
    onOpenChange={(details) => {
        isOpen = details.open;
    }}
>
    <Dialog.Backdrop class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm" />
    <Dialog.Positioner class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <Dialog.Content class="w-full max-w-xl rounded-xl border bg-white shadow-xl dark:bg-slate-900">

            <!-- Header -->
            <div class="flex items-center justify-between border-b px-5 py-4">
                <div>
                    <Dialog.Title class="text-base font-semibold">Submit delay report</Dialog.Title>
                    <Dialog.Description class="text-xs text-muted-foreground mt-0.5">
                        Use this when the stage is blocked, delayed, or overdue.
                    </Dialog.Description>
                </div>
                <Dialog.CloseTrigger class="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground">
                    <X class="size-4" />
                </Dialog.CloseTrigger>
            </div>

            <form onsubmit={submit} class="space-y-4 px-5 py-4">

                <!-- Reason -->
                <div class="space-y-1.5">
                    <label for="reason_category" class="text-sm font-medium">Reason category</label>
                    <select
                        id="reason_category"
                        name="reason_category"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        bind:value={$form.reason_category}
                        required
                    >
                        <option value="PARTS_DELAY">Parts delay</option>
                        <option value="EXTERNAL_VENDOR">External vendor</option>
                        <option value="REWORK">Rework</option>
                        <option value="CLIENT_APPROVAL">Client approval</option>
                        <option value="ADDITIONAL_DAMAGE">Additional damage</option>
                        <option value="OTHER">Other</option>
                    </select>
                    <InputError message={$form.errors.reason_category} />
                </div>

                <!-- Explanation -->
                <div class="space-y-1.5">
                    <label for="explanation" class="text-sm font-medium">Explanation</label>
                    <textarea
                        id="explanation"
                        name="explanation"
                        class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm resize-none"
                        placeholder="Describe the reason for the delay..."
                        bind:value={$form.explanation}
                        required
                    ></textarea>
                    <InputError message={$form.errors.explanation} />
                </div>

                <!-- Proposed ETA -->
                <div class="space-y-1.5">
                    <label for="proposed_eta" class="text-sm font-medium">Proposed ETA</label>
                    <input
                        id="proposed_eta"
                        type="datetime-local"
                        name="proposed_eta"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        bind:value={$form.proposed_eta}
                        required
                    />
                    <InputError message={$form.errors.proposed_eta} />
                </div>

                <!-- File upload -->
                <div class="space-y-1.5">
                    <p class="text-sm font-medium">Attachments <span class="text-muted-foreground font-normal">(up to 5 files, 10 MB each)</span></p>

                    <!-- Drop zone -->
                    <button
                        type="button"
                        class="relative flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-6 text-sm text-muted-foreground transition-colors
                            {dragging ? 'border-primary bg-primary/5 text-primary' : 'border-border hover:border-primary/50 hover:bg-muted/30'}"
                        onclick={() => fileInput.click()}
                        ondragover={(e) => { e.preventDefault(); dragging = true; }}
                        ondragleave={() => dragging = false}
                        ondrop={(e) => { e.preventDefault(); dragging = false; addFiles(e.dataTransfer?.files ?? null); }}
                    >
                        <Upload class="size-5 {dragging ? 'text-primary' : 'text-muted-foreground'}" />
                        <span>Drag & drop files here or <span class="text-primary underline underline-offset-2">browse</span></span>
                        {#if $form.attachments.length > 0}
                            <span class="text-xs">{$form.attachments.length}/5 file{$form.attachments.length !== 1 ? 's' : ''} selected</span>
                        {/if}
                    </button>

                    <!-- Hidden file input -->
                    <input
                        bind:this={fileInput}
                        type="file"
                        multiple
                        accept="image/*,.pdf,.doc,.docx"
                        class="hidden"
                        onchange={(e) => addFiles((e.target as HTMLInputElement).files)}
                    />

                    <!-- File list -->
                    {#if $form.attachments.length > 0}
                        <ul class="space-y-1.5 mt-2">
                            {#each $form.attachments as file, i (file.name + i)}
                                <li class="flex items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-xs">
                                    <Paperclip class="size-3.5 shrink-0 text-muted-foreground" />
                                    <span class="flex-1 truncate font-medium">{file.name}</span>
                                    <span class="shrink-0 text-muted-foreground">{formatSize(file.size)}</span>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded p-0.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                        onclick={() => removeFile(i)}
                                    >
                                        <X class="size-3.5" />
                                    </button>
                                </li>
                            {/each}
                        </ul>
                    {/if}

                    <!-- Upload progress -->
                    {#if $form.progress}
                        <div class="mt-2 space-y-1">
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>Uploading…</span>
                                <span>{$form.progress.percentage}%</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary transition-all duration-300"
                                    style="width: {$form.progress.percentage}%"
                                ></div>
                            </div>
                        </div>
                    {/if}

                    <InputError message={$form.errors.attachments} />
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 border-t pt-4">
                    <button
                        type="submit"
                        class="btn preset-filled"
                        disabled={$form.processing}
                    >
                        {$form.processing ? 'Submitting…' : 'Submit delay report'}
                    </button>
                    <Dialog.CloseTrigger class="btn preset-tonal">Cancel</Dialog.CloseTrigger>
                </div>

            </form>
        </Dialog.Content>
    </Dialog.Positioner>
</Dialog>
