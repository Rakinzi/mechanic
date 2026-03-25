<script lang="ts">
    type GalleryMedia = {
        id: number;
        original_url?: string;
        file_name?: string;
        mime_type?: string;
    };

    let { media }: { media: GalleryMedia[] } = $props();

    function isImage(item: GalleryMedia): boolean {
        return !!item.mime_type?.startsWith('image/');
    }
</script>

{#if media.length === 0}
    <p class="text-sm text-muted-foreground">No photos uploaded yet.</p>
{:else}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        {#each media as item (item.id)}
            <a href={item.original_url} target="_blank" rel="noopener noreferrer" download={!isImage(item) ? item.file_name : undefined} class="overflow-hidden rounded border flex items-center justify-center bg-muted/30 h-32">
                {#if isImage(item) && item.original_url}
                    <img src={item.original_url} alt={item.file_name ?? 'Stage media'} class="h-32 w-full object-cover" />
                {:else}
                    <div class="flex flex-col items-center gap-1 px-2 py-3 text-center">
                        <span class="text-2xl">📎</span>
                        <span class="text-xs font-medium truncate w-full text-center">{item.file_name ?? 'Download'}</span>
                    </div>
                {/if}
            </a>
        {/each}
    </div>
{/if}
