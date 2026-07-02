<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Settings\Company;
use App\Models\Settings\User;

class CompanyConsolidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    public function test_can_set_company_to_all()
    {
        $response = $this->actingAs($this->user)
            ->post('/set-company', [
                'company_id' => 'all'
            ]);

        $response->assertStatus(302);
        $this->assertEquals('all', session('company_id'));
    }

    public function test_prevent_write_in_consolidated_mode()
    {
        // Set mode konsolidasi
        $this->withSession(['company_id' => 'all']);

        // Akses route post (misal buat customer dummy endpoint untuk test middleware)
        $response = $this->actingAs($this->user)
            ->post('/customers', ['name' => 'Test']);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['error']);
    }
}
