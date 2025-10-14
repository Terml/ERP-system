<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CreateOrder extends TestCase
{
    use DatabaseTransactions, WithFaker;
    protected User $manager;
    protected User $dispatcher;
    protected Company $company;
    protected Product $product;
    protected function setUp(): void
    {
        parent::setUp();
        $this->createRoles();
        $this->createUsers();
        $this->createTestData();
    }
    private function createRoles(): void
    {
        Role::create(['role' => 'admin', 'description' => 'админ']);
        Role::create(['role' => 'manager', 'description' => 'менеджер']);
        Role::create(['role' => 'dispatcher', 'description' => 'диспетчер']);
        Role::create(['role' => 'master', 'description' => 'мастер']);
        Role::create(['role' => 'otk', 'description' => 'ОТК']);
    }
    private function createUsers(): void
    {
        $this->manager = User::create([
            'login' => 'manager_test',
            'password' => Hash::make('password123'),
        ]);
        $this->manager->syncRoles(['manager']);
        $this->dispatcher = User::create([
            'login' => 'dispatcher_test',
            'password' => Hash::make('password123'),
        ]);
        $this->dispatcher->syncRoles(['dispatcher']);
    }
    private function createTestData(): void
    {
        $this->company = Company::create([
            'name' => 'Тестовая компания',
        ]);
        $this->product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт',
        ]);
    }
    public function test_manager_can_create_order_successfully()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'quantity',
                    'deadline',
                    'status',
                    'created_at',
                    'updated_at',
                    'company' => [
                        'id',
                        'name',
                    ],
                    'product' => [
                        'id',
                        'name',
                        'type',
                        'unit',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'quantity' => 100,
                    'company' => [
                        'id' => $this->company->id,
                        'name' => $this->company->name,
                    ],
                    'product' => [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                        'type' => $this->product->type,
                        'unit' => $this->product->unit,
                    ],
                ],
            ]);
        $responseData = $response->json('data');
        $this->assertEquals($this->company->id, $responseData['company']['id']);
        $this->assertEquals($this->product->id, $responseData['product']['id']);
        $this->assertEquals(100, $responseData['quantity']);
    }
    public function test_non_manager_cannot_create_order()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->dispatcher, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(403);
    }
    public function test_unauthenticated_user_cannot_create_order()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->postJson('/api/orders', $orderData);
        $response->assertStatus(401);
    }
    public function test_validation_requires_company_id()
    {
        $orderData = [
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id']);
    }
    public function test_validation_requires_product_id()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }
    public function test_validation_requires_quantity()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
    public function test_validation_requires_deadline()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }
    public function test_validation_company_id_must_exist()
    {
        $orderData = [
            'company_id' => 999,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id']);
    }
    public function test_validation_product_id_must_exist()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => 999,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }
    public function test_validation_quantity_must_be_positive()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 0,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
    public function test_validation_quantity_must_not_exceed_maximum()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 1001,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
    public function test_validation_deadline_must_be_in_future()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->subDays(1)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }
    public function test_validation_deadline_must_be_valid_date()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => 'invalid-date',
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }
    public function test_validation_deadline_cannot_be_too_far_in_future()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addYears(3)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }
    public function test_order_created_with_correct_default_status()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(201);
        $responseData = $response->json('data');
        $this->assertEquals($this->company->id, $responseData['company']['id']);
        $this->assertEquals($this->product->id, $responseData['product']['id']);
    }
    public function test_order_includes_company_and_product_relationships()
    {
        $orderData = [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/orders', $orderData);
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'company' => [
                        'id' => $this->company->id,
                        'name' => $this->company->name,
                    ],
                    'product' => [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                        'type' => $this->product->type,
                        'unit' => $this->product->unit,
                    ],
                ],
            ]);
    }
}
