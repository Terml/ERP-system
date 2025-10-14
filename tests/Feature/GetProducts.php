<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class GetProducts extends TestCase
{
    use DatabaseTransactions, WithFaker;
    protected User $admin;
    protected User $manager;
    protected User $dispatcher;
    protected Product $product1;
    protected Product $product2;
    protected Product $product3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRoles();
        $this->createUsers();
        $this->createTestProducts();
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
        $this->admin = User::create([
            'login' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);
        $this->admin->syncRoles(['admin']);
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
    private function createTestProducts(): void
    {
        $this->product1 = Product::create([
            'name' => 'Продукт 1',
            'type' => 'product',
            'unit' => 'шт',
        ]);
        $this->product2 = Product::create([
            'name' => 'Материал 1',
            'type' => 'material',
            'unit' => 'кг',
        ]);
        $this->product3 = Product::create([
            'name' => 'Материал 2',
            'type' => 'material',
            'unit' => 'м',
        ]);
    }
    public function test_admin_can_get_all_products()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'unit',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'count',
            ]);

        $responseData = $response->json('data');
        $this->assertCount(3, $responseData);
    }
    public function test_manager_can_get_all_products()
    {
        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/products');

        $response->assertStatus(200);
    }
    public function test_dispatcher_can_get_all_products()
    {
        $response = $this->actingAs($this->dispatcher, 'api')
            ->getJson('/api/products');

        $response->assertStatus(200);
    }
    public function test_unauthenticated_user_can_get_products()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
    }
    public function test_can_get_products_with_parameters()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products?page=1&per_page=2');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'count',
            ]);
        $responseData = $response->json('data');
        $this->assertCount(3, $responseData);
    }
    public function test_can_filter_products_by_type()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products?type=product');
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('product', $responseData[0]['type']);
    }
    public function test_can_search_products_by_name()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products?search=Продукт');
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertStringContainsString('Продукт', $responseData[0]['name']);
    }
    public function test_can_sort_products_by_name()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products?sort=name&order=asc');
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(3, $responseData);
        $names = array_column($responseData, 'name');
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }
    public function test_can_get_specific_product_by_id()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/products/{$this->product1->id}");
        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->product1->id,
                'name' => $this->product1->name,
                'type' => $this->product1->type,
                'unit' => $this->product1->unit,
            ]);
    }
    public function test_getting_nonexistent_product_returns_404()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products/999');
        $response->assertStatus(404);
    }
    public function test_can_combine_filters_and_search()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products?type=material&search=Материал 1');
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('material', $responseData[0]['type']);
        $this->assertStringContainsString('Материал 1', $responseData[0]['name']);
    }
    public function test_empty_search_returns_empty_array()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/products?search=НесуществующийПродукт');
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(0, $responseData);
    }
}
