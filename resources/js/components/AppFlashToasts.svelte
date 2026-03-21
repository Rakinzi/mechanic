<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { Toast, createToaster } from '@skeletonlabs/skeleton-svelte';

    type FlashPayload = {
        success?: string | null;
        error?: string | null;
        info?: string | null;
    };

    const toaster = createToaster({
        placement: 'top-end',
        max: 5,
        overlap: false,
    });

    let lastFlash = $state<FlashPayload>({});

    $effect(() => {
        const flash = ($page.props.flash ?? {}) as FlashPayload;

        if (flash.success && flash.success !== lastFlash.success) {
            lastFlash = { ...lastFlash, success: flash.success };
            toaster.success({ title: 'Success', description: flash.success, closable: true });
        }

        if (flash.error && flash.error !== lastFlash.error) {
            lastFlash = { ...lastFlash, error: flash.error };
            toaster.error({ title: 'Error', description: flash.error, closable: true });
        }

        if (flash.info && flash.info !== lastFlash.info) {
            lastFlash = { ...lastFlash, info: flash.info };
            toaster.info({ title: 'Info', description: flash.info, closable: true });
        }
    });
</script>

<Toast.Group {toaster}>
    {#snippet children(toast)}
        <Toast {toast} class="w-80 rounded-lg border border-slate-200 bg-white p-3 shadow-lg dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <Toast.Title class="text-sm font-semibold">{toast.title ?? 'Notice'}</Toast.Title>
                    <Toast.Description class="text-xs text-muted-foreground">{String(toast.description ?? '')}</Toast.Description>
                </div>
                <Toast.CloseTrigger class="text-xs text-muted-foreground hover:text-foreground">Close</Toast.CloseTrigger>
            </div>
        </Toast>
    {/snippet}
</Toast.Group>
