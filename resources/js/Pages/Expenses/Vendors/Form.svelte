<script>
    import AppLayout from '../../../Layouts/AppLayout.svelte';
    import { useForm, router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';

    export let vendor;
    export let isEdit;

    const form = useForm({
        vendor_code: vendor.vendor_code || '',
        name: vendor.name || '',
        npwp: vendor.npwp || '',
        phone: vendor.phone || '',
        email: vendor.email || '',
        address: vendor.address || ''
    });

    function submit() {
        if (isEdit) {
            $form.put(`/vendors/${vendor.id}`);
        } else {
            $form.post('/vendors');
        }
    }
</script>

<AppLayout title={isEdit ? 'Edit Vendor' : 'Create Vendor'}>
    <div class="p-6 max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">{isEdit ? 'Edit Vendor' : 'Create Vendor'}</h1>

        <form on:submit|preventDefault={submit} class="space-y-4 bg-white p-6 rounded-lg shadow">
            <div>
                <label class="block text-sm font-medium mb-1">Vendor Code</label>
                <Input bind:value={$form.vendor_code} placeholder="Optional" />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <Input bind:value={$form.name} required />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">NPWP</label>
                <Input bind:value={$form.npwp} />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <Input bind:value={$form.phone} />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <Input type="email" bind:value={$form.email} />
            </div>

            <div class="pt-4 flex justify-end gap-2">
                <Button variant="outline" type="button" on:click={() => router.get('/vendors')}>Cancel</Button>
                <Button type="submit" disabled={$form.processing}>Save</Button>
            </div>
        </form>
    </div>
</AppLayout>
