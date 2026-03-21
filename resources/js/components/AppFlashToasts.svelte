<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { Toast, createToaster } from '@skeletonlabs/skeleton-svelte';
    import CheckCircle from 'lucide-svelte/icons/check-circle-2';
    import AlertCircle from 'lucide-svelte/icons/alert-circle';
    import Info from 'lucide-svelte/icons/info';

    type FlashPayload = {
        success?: string | null;
        error?: string | null;
        info?: string | null;
    };

    const toaster = createToaster({
        placement: 'bottom-right',
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

<Toast.Group {toaster} class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2">
    {#snippet children(toast)}
        <Toast
            {toast}
            class="flex w-80 items-start gap-3 rounded-lg border bg-white p-3.5 shadow-lg dark:bg-slate-900
                {toast.type === 'success' ? 'border-l-4 border-l-emerald-500' :
                 toast.type === 'error'   ? 'border-l-4 border-l-red-500' :
                 'border-l-4 border-l-blue-500'}"
        >
            <div class="mt-0.5 shrink-0">
                {#if toast.type === 'success'}
                    <CheckCircle class="size-4 text-emerald-500" />
                {:else if toast.type === 'error'}
                    <AlertCircle class="size-4 text-red-500" />
                {:else}
                    <Info class="size-4 text-blue-500" />
                {/if}
            </div>
            <div class="min-w-0 flex-1">
                <Toast.Title class="text-sm font-semibold leading-tight">{toast.title ?? 'Notice'}</Toast.Title>
                <Toast.Description class="mt-0.5 text-xs text-muted-foreground leading-snug">{String(toast.description ?? '')}</Toast.Description>
            </div>
            <Toast.CloseTrigger class="shrink-0 rounded p-0.5 text-muted-foreground hover:text-foreground">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </Toast.CloseTrigger>
        </Toast>
    {/snippet}
</Toast.Group>
