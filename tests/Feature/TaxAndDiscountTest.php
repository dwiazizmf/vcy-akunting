<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Settings\Tax;
use App\Models\Settings\Discount;
use App\Models\Settings\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxAndDiscountTest extends TestCase
{
    use RefreshDatabase;

    private $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'enabled' => true,
        ]);

        session(['company_id' => $this->company->id]);
    }

    public function test_percentage_tax_rate_max_100()
    {
        // 1. Valid percentage tax rate (<= 100)
        $response = $this->postJson('/api/settings/taxes', [
            'name' => 'Percentage Tax',
            'type' => 'percentage',
            'rate' => 11,
            'enabled' => true,
        ]);
        $response->assertStatus(200);

        // 2. Invalid percentage tax rate (> 100)
        $response = $this->postJson('/api/settings/taxes', [
            'name' => 'Percentage Tax Invalid',
            'type' => 'percentage',
            'rate' => 110,
            'enabled' => true,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rate');
    }

    public function test_fixed_tax_rate_can_be_more_than_100()
    {
        // 1. Valid fixed tax rate (e.g. 50000)
        $response = $this->postJson('/api/settings/taxes', [
            'name' => 'Fixed Tax',
            'type' => 'fixed',
            'rate' => 50000,
            'enabled' => true,
        ]);
        $response->assertStatus(200);
        
        // Disable global scope for assertion if needed, or query directly
        $tax = Tax::withoutGlobalScopes()->first();
        $this->assertNotNull($tax);
        $this->assertEquals(50000, (float) $tax->rate);
    }

    public function test_percentage_discount_rate_max_100()
    {
        // 1. Valid percentage discount rate (<= 100)
        $response = $this->postJson('/api/settings/discounts', [
            'name' => 'Percentage Discount',
            'type' => 'percentage',
            'rate' => 50,
            'enabled' => true,
        ]);
        $response->assertStatus(200);

        // 2. Invalid percentage discount rate (> 100)
        $response = $this->postJson('/api/settings/discounts', [
            'name' => 'Percentage Discount Invalid',
            'type' => 'percentage',
            'rate' => 120,
            'enabled' => true,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rate');
    }

    public function test_fixed_discount_rate_can_be_more_than_100()
    {
        // 1. Valid fixed discount rate (e.g. 75000)
        $response = $this->postJson('/api/settings/discounts', [
            'name' => 'Fixed Discount',
            'type' => 'fixed',
            'rate' => 75000,
            'enabled' => true,
        ]);
        $response->assertStatus(200);
        
        $discount = Discount::first();
        $this->assertNotNull($discount);
        $this->assertEquals(75000, (float) $discount->rate);
    }
}
