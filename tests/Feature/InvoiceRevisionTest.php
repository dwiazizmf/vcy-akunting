<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Incomes\Invoice;
use App\Models\Incomes\Customer;
use App\Models\Settings\Company;
use App\Models\Settings\User;
use App\Models\Accounting\Account;
use App\Models\Settings\InvoiceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceRevisionTest extends TestCase
{
    use RefreshDatabase;

    private $company;
    private $customer;
    private $user;
    private $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'VCY Logistics',
            'enabled' => true,
        ]);

        // Seed Chart of Accounts
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);

        $this->account = Account::first();

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corp',
            'account_id' => $this->account->id,
            'address' => '123 Acme Road',
            'npwp' => '01.234.567.8-999.000',
        ]);

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@vcy.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);
        session(['company_id' => $this->company->id]);
    }

    public function test_invoice_revision_flow()
    {
        // 1. Create a base invoice
        $baseInvoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'invoice_number' => '00001',
            'invoice_text' => '00001/VI/2026',
            'invoiced_at' => '2026-06-22',
            'due_at' => '2026-06-29',
            'subtotal' => 100000,
            'grand_total' => 100000,
            'invoice_status_code' => 'draft',
            'payment_status' => 'unpaid',
        ]);

        // 2. Submit a revision of the base invoice
        $response = $this->post('/invoices', [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_address' => '123 Acme Road',
            'customer_npwp' => '01.234.567.8-999.000',
            'invoiced_at' => '2026-06-22',
            'due_at' => '2026-06-29',
            'revised_invoice_id' => $baseInvoice->id,
            'items' => [
                [
                    'name' => 'Revised Item',
                    'quantity' => 1,
                    'price' => 120000,
                ]
            ]
        ]);

        $response->assertRedirect(route('invoices.index'));

        // Assert base invoice is now voided
        $baseInvoice->refresh();
        $this->assertEquals('void', $baseInvoice->invoice_status_code);

        // Assert revision invoice exists with .R1 tag
        $revision = Invoice::where('r_invoice_text', $baseInvoice->invoice_text)->first();
        $this->assertNotNull($revision);
        $this->assertEquals('00001.R1', $revision->invoice_number);
        $this->assertEquals('00001/VI/2026.R1', $revision->invoice_text);
        $this->assertEquals('draft', $revision->invoice_status_code);
        $this->assertEquals('123 Acme Road', $revision->customer_address);
        $this->assertEquals('01.234.567.8-999.000', $revision->customer_npwp);

        // 3. Submit a second revision (revising the first revision .R1)
        $response = $this->post('/invoices', [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'invoiced_at' => '2026-06-22',
            'due_at' => '2026-06-29',
            'revised_invoice_id' => $revision->id,
            'items' => [
                [
                    'name' => 'Second Revised Item',
                    'quantity' => 1,
                    'price' => 150000,
                ]
            ]
        ]);

        // Assert revision .R1 is now voided
        $revision->refresh();
        $this->assertEquals('void', $revision->invoice_status_code);

        // Assert second revision exists with .R2 tag
        $secondRevision = Invoice::where('r_invoice_text', $revision->invoice_text)->first();
        $this->assertNotNull($secondRevision);
        $this->assertEquals('00001.R2', $secondRevision->invoice_number);
        $this->assertEquals('00001/VI/2026.R2', $secondRevision->invoice_text);
    }

    public function test_automatic_faktur_pajak_generation()
    {
        // Setup invoice setting for tax invoice (faktur_periode)
        InvoiceSetting::create([
            'company_id' => $this->company->id,
            'first_faktur' => '05',
            'second_faktur' => '0',
            'third_faktur' => '002',
            'fourth_faktur' => '25',
            'no_awal' => 1,
            'no_akhir' => 5,
        ]);

        // 1. Create first invoice with isFaktur = true
        $response = $this->post('/invoices', [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'invoiced_at' => '2026-06-22',
            'due_at' => '2026-06-29',
            'isFaktur' => true,
            'items' => [
                [
                    'name' => 'Item 1',
                    'quantity' => 1,
                    'price' => 100000,
                ]
            ]
        ]);

        $response->assertRedirect(route('invoices.index'));
        $invoice1 = Invoice::orderBy('id', 'desc')->first();
        $this->assertTrue((bool)$invoice1->isFaktur);
        $this->assertEquals('1', $invoice1->no_faktur_int);
        $this->assertEquals('050.002-25.00000001', $invoice1->no_faktur_pajak);

        // 2. Create second invoice with isFaktur = true
        $response2 = $this->post('/invoices', [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'invoiced_at' => '2026-06-22',
            'due_at' => '2026-06-29',
            'isFaktur' => true,
            'items' => [
                [
                    'name' => 'Item 2',
                    'quantity' => 1,
                    'price' => 200000,
                ]
            ]
        ]);

        $invoice2 = Invoice::orderBy('id', 'desc')->first();
        $this->assertTrue((bool)$invoice2->isFaktur);
        $this->assertEquals('2', $invoice2->no_faktur_int);
        $this->assertEquals('050.002-25.00000002', $invoice2->no_faktur_pajak);
    }

    public function test_is_fcl_and_no_container_storage()
    {
        $response = $this->post('/invoices', [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'invoiced_at' => '2026-06-22',
            'due_at' => '2026-06-29',
            'isFCL' => true,
            'no_container' => 'CONT-1234',
            'items' => [
                [
                    'name' => 'FCL Item',
                    'quantity' => 1,
                    'price' => 500000,
                ]
            ]
        ]);

        $response->assertRedirect(route('invoices.index'));
        $invoice = Invoice::orderBy('id', 'desc')->first();
        $this->assertTrue((bool)$invoice->isFCL);
        $this->assertEquals('CONT-1234', $invoice->no_container);
    }
}
