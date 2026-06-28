<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<img
    src="/images/branding/logo-light.jpeg"
    alt="Technician logo"
    class={`block rounded-sm object-cover dark:hidden ${className}`}
    {...rest}
/>
<img src="/images/branding/logo-dark.jpeg" alt="Technician logo" class={`hidden rounded-sm object-cover dark:block ${className}`} />
