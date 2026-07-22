<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import CustomerForm from './CustomerForm.svelte';

  export let accounts = [];
</script>

<svelte:head>
  <title>Tambah Customer - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="max-w-4xl mx-auto py-6">
    <CustomerForm {accounts} />
  </div>
</AppLayout>
