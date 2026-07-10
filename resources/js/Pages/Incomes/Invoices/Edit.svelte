<script>
    import InvoiceForm from "./InvoiceForm.svelte";

    export let invoice = null;
    export let activeTaxes = [];
    export let activeDiscounts = [];
    export let customers = [];
    export let revenueAccounts = [];
    export let invoiceTypes = [];
    export let errors = {};
    export let companies = [];
    export let active_company_id = null;
    export let invoices = [];
</script>

<InvoiceForm
    isEdit={true}
    {invoice}
    {activeTaxes}
    {activeDiscounts}
    {customers}
    {revenueAccounts}
    {invoiceTypes}
    {errors}
    {companies}
    {active_company_id}
    {invoices}
/>
