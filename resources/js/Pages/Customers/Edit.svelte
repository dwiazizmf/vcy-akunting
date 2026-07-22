<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import CustomerForm from './CustomerForm.svelte';

  export let customer;
  export let accounts = [];
</script>

<svelte:head>
  <title>Edit Customer - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="max-w-4xl mx-auto py-6">
    <CustomerForm {customer} {accounts} />
  </div>
</AppLayout>
