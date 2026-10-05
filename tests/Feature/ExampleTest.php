<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\KodeAkun;
use App\Models\Budget;
use App\Models\Transaksi;
use App\Models\TransaksiBank;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip tests if database is not properly set up
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
            return;
        }
        
        // Create a test user for authentication
        try {
            $this->user = User::factory()->create();
        } catch (\Exception $e) {
            // If factory fails, create user manually
            $this->user = User::create([
                'User_name' => 'Test User',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'Role' => 'User'
            ]);
        }
    }

    /**
     * Test index method - GET /budget-actual
     */
    public function test_index_returns_successful_response(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual');
        
        $response->assertStatus(200);
        $response->assertViewIs('budget-actual.index');
        $response->assertViewHas(['budgetActualData', 'projects', 'akuns']);
    }

    /**
     * Test index method dengan filter tanggal
     */
    public function test_index_with_date_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual', [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31'
        ]);
        
        $response->assertStatus(200);
        $response->assertViewIs('budget-actual.index');
    }

    /**
     * Test index method dengan filter project_id
     */
    public function test_index_with_project_filter(): void
    {
        // Try to create in project table (check if table exists first)
        try {
            if (DB::getSchemaBuilder()->hasTable('project')) {
                Project::create([
                    'Kode_project' => 'PROJ001',
                    'Nama_Project' => 'Project Test',
                    'Deskripsi' => 'Test Project Description',
                    'Tanggal_Mulai' => now()->toDateString(),
                    'Status' => 'Aktif'
                ]);
            } elseif (DB::getSchemaBuilder()->hasTable('projects')) {
                DB::table('projects')->insertOrIgnore([
                    'kode_project' => 'PROJ001',
                    'nama_project' => 'Project Test'
                ]);
            }
        } catch (\Exception $e) {
            // Project might already exist or table structure different
        }
        
        $response = $this->actingAs($this->user)->get('/budget-actual', [
            'project_id' => 'PROJ001'
        ]);
        
        $response->assertStatus(200);
        $response->assertViewIs('budget-actual.index');
    }

    /**
     * Test index method dengan filter akun_kode
     */
    public function test_index_with_akun_kode_filter(): void
    {
        $akun = KodeAkun::create([
            'kode_akun' => '1001',
            'nama' => 'Kas',
            'tipe' => 'Debit'
        ]);
        
        $response = $this->actingAs($this->user)->get('/budget-actual', [
            'akun_kode' => '1001'
        ]);
        
        $response->assertStatus(200);
        $response->assertViewIs('budget-actual.index');
    }

    /**
     * Test index method validasi tanggal - to_date harus setelah from_date
     */
    public function test_index_validates_date_range(): void
    {
        // Test with session to allow redirect back
        $response = $this->actingAs($this->user)
            ->withSession(['_previous' => ['url' => '/budget-actual']])
            ->get('/budget-actual', [
                'from_date' => '2024-12-31',
                'to_date' => '2024-01-01'
            ]);
        
        // Should redirect back with validation errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors('to_date');
    }

    /**
     * Test index method tanpa tanggal (default wide range)
     */
    public function test_index_without_dates_uses_wide_range(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual');
        
        $response->assertStatus(200);
        $response->assertViewIs('budget-actual.index');
        
        $response->assertViewHas('displayFromDate');
        $response->assertViewHas('displayToDate');
    }

    /**
     * Test getData method - GET /budget-actual/data (AJAX)
     */
    public function test_get_data_returns_json_response(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual/data');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data',
                'summary' => [
                    'grand_total_budget',
                    'grand_total_actual',
                    'grand_total_variance',
                    'grand_total_variance_percent',
                    'from_date',
                    'to_date',
                    'total_accounts'
                ]
            ]
        ]);
    }

    /**
     * Test getData method dengan filter
     */
    public function test_get_data_with_filters(): void
    {
        // Try to create in project table (check if table exists first)
        try {
            if (DB::getSchemaBuilder()->hasTable('project')) {
                Project::create([
                    'Kode_project' => 'PROJ001',
                    'Nama_Project' => 'Project Test',
                    'Deskripsi' => 'Test Project Description',
                    'Tanggal_Mulai' => now()->toDateString(),
                    'Status' => 'Aktif'
                ]);
            } elseif (DB::getSchemaBuilder()->hasTable('projects')) {
                DB::table('projects')->insertOrIgnore([
                    'kode_project' => 'PROJ001',
                    'nama_project' => 'Project Test'
                ]);
            }
        } catch (\Exception $e) {
            // Project might already exist or table structure different
        }
        
        $akun = KodeAkun::create([
            'kode_akun' => '1001',
            'nama' => 'Kas',
            'tipe' => 'Debit'
        ]);
        
        $response = $this->actingAs($this->user)->get('/budget-actual/data', [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'project_id' => 'PROJ001',
            'akun_kode' => '1001'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    /**
     * Test getData method validasi tanggal
     */
    public function test_get_data_validates_date_range(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual/data', [
            'from_date' => '2024-12-31',
            'to_date' => '2024-01-01'
        ]);
        
        // Should return JSON error response
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test exportExcel method - GET /budget-actual/export-excel
     */
    public function test_export_excel_requires_dates(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual/export-excel');
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['from_date', 'to_date']);
    }

    /**
     * Test exportExcel method dengan parameter valid
     */
    public function test_export_excel_with_valid_params(): void
    {
        // Try to create in project table (check if table exists first)
        try {
            if (DB::getSchemaBuilder()->hasTable('project')) {
                Project::create([
                    'Kode_project' => 'PROJ001',
                    'Nama_Project' => 'Project Test',
                    'Deskripsi' => 'Test Project Description',
                    'Tanggal_Mulai' => now()->toDateString(),
                    'Status' => 'Aktif'
                ]);
            } elseif (DB::getSchemaBuilder()->hasTable('projects')) {
                DB::table('projects')->insertOrIgnore([
                    'kode_project' => 'PROJ001',
                    'nama_project' => 'Project Test'
                ]);
            }
        } catch (\Exception $e) {
            // Project might already exist or table structure different
        }
        
        $response = $this->actingAs($this->user)->get('/budget-actual/export-excel', [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31'
        ]);
        
        // Should return either Excel file or CSV
        $this->assertContains($response->status(), [200, 302]);
    }

    /**
     * Test exportExcel method validasi tanggal
     */
    public function test_export_excel_validates_date_range(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual/export-excel', [
            'from_date' => '2024-12-31',
            'to_date' => '2024-01-01'
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors('to_date');
    }

    /**
     * Test exportPdf method - GET /budget-actual/export-pdf
     */
    public function test_export_pdf_requires_dates(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual/export-pdf');
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['from_date', 'to_date']);
    }

    /**
     * Test exportPdf method dengan parameter valid
     */
    public function test_export_pdf_with_valid_params(): void
    {
        // Try to create in project table (check if table exists first)
        try {
            if (DB::getSchemaBuilder()->hasTable('project')) {
                Project::create([
                    'Kode_project' => 'PROJ001',
                    'Nama_Project' => 'Project Test',
                    'Deskripsi' => 'Test Project Description',
                    'Tanggal_Mulai' => now()->toDateString(),
                    'Status' => 'Aktif'
                ]);
            } elseif (DB::getSchemaBuilder()->hasTable('projects')) {
                DB::table('projects')->insertOrIgnore([
                    'kode_project' => 'PROJ001',
                    'nama_project' => 'Project Test'
                ]);
            }
        } catch (\Exception $e) {
            // Project might already exist or table structure different
        }
        
        $response = $this->actingAs($this->user)->get('/budget-actual/export-pdf', [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31'
        ]);
        
        // Should return either PDF or HTML
        $this->assertContains($response->status(), [200, 302]);
    }

    /**
     * Test exportPdf method validasi tanggal
     */
    public function test_export_pdf_validates_date_range(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual/export-pdf', [
            'from_date' => '2024-12-31',
            'to_date' => '2024-01-01'
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors('to_date');
    }

    /**
     * Test index method dengan data budget dan actual
     */
    public function test_index_with_budget_and_actual_data(): void
    {
        // Try to create in project table (check if table exists first)
        try {
            if (DB::getSchemaBuilder()->hasTable('project')) {
                Project::create([
                    'Kode_project' => 'PROJ001',
                    'Nama_Project' => 'Project Test',
                    'Deskripsi' => 'Test Project Description',
                    'Tanggal_Mulai' => now()->toDateString(),
                    'Status' => 'Aktif'
                ]);
            } elseif (DB::getSchemaBuilder()->hasTable('projects')) {
                DB::table('projects')->insertOrIgnore([
                    'kode_project' => 'PROJ001',
                    'nama_project' => 'Project Test'
                ]);
            }
        } catch (\Exception $e) {
            // Project might already exist or table structure different
        }
        
        $akun = KodeAkun::create([
            'kode_akun' => '1001',
            'nama' => 'Kas',
            'tipe' => 'Debit'
        ]);
        
        Budget::create([
            'kode_project' => 'PROJ001',
            'tanggal' => '2024-06-15',
            'budget' => 1000000,
            'deskripsi' => 'Test Budget Description'
        ]);
        
        Transaksi::create([
            'tanggal' => '2024-06-15',
            'kode_project' => 'PROJ001',
            'akun_kode' => '1001',
            'nominal' => 500000,
            'jumlah' => 500000,
            'transaksi_type' => 1,
            'deskripsi' => 'Test Transaksi Description',
            'budget' => 0
        ]);
        
        $response = $this->actingAs($this->user)->get('/budget-actual', [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31'
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('budget-actual.index');
        
        $response->assertViewHas('budgetActualData');
        $budgetActualData = $response->viewData('budgetActualData');
        $this->assertIsArray($budgetActualData);
    }

    /**
     * Test getData method response structure
     */
    public function test_get_data_response_structure(): void
    {
        $response = $this->actingAs($this->user)->get('/budget-actual/data');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('summary', $data['data']);
        $this->assertIsArray($data['data']['data']);
        $this->assertIsArray($data['data']['summary']);
    }

    /**
     * Test unauthenticated access redirects to login
     */
    public function test_unauthenticated_access_redirects(): void
    {
        $response = $this->get('/budget-actual');
        
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
