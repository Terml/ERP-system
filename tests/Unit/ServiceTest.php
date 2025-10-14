<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\ProductionTaskService;
use App\Services\CompanyService;
use App\Services\RoleService;
use App\Services\CacheService;
use App\Services\DocumentService;
use App\Models\Product;
use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\TaskComponent;
use App\DTOs\CreateProductDTO;
use App\DTOs\UpdateProductDTO;
use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\DTOs\CreateProductionTaskDTO;
use App\DTOs\TaskComponentDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mockery;

class ServiceTest extends TestCase
{
    use DatabaseTransactions, WithFaker;
    protected ProductService $productService;
    protected OrderService $orderService;
    protected ProductionTaskService $taskService;
    protected CompanyService $companyService;
    protected RoleService $roleService;
    protected CacheService $cacheService;
    protected DocumentService $documentService;
    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new CacheService();
        $this->productService = new ProductService(new Product(), $this->cacheService);
        $this->orderService = new OrderService(new Order(), $this->cacheService);
        $this->taskService = new ProductionTaskService(new ProductionTask());
        $this->companyService = new CompanyService(new Company(), $this->cacheService);
        $this->roleService = new RoleService(new Role(), $this->cacheService);
        $this->documentService = new DocumentService();
    }
    public function test_product_service_can_create_product()
    {
        $dto = new CreateProductDTO('Тестовый продукт', 'product', 'шт');
        $product = $this->productService->createProduct($dto);
        $this->assertNotNull($product);
        $this->assertEquals('Тестовый продукт', $product->name);
        $this->assertEquals('product', $product->type);
        $this->assertEquals('шт', $product->unit);
    }
    public function test_product_service_can_update_product()
    {
        $product = Product::create([
            'name' => 'Старое название',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $dto = new UpdateProductDTO('Новое название', null, 'кг');
        $updatedProduct = $this->productService->updateProduct($product->id, $dto);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals('Новое название', $updatedProduct->name);
        $this->assertEquals('product', $updatedProduct->type);
        $this->assertEquals('кг', $updatedProduct->unit);
    }
    public function test_product_service_can_delete_product()
    {
        $product = Product::create([
            'name' => 'Продукт для удаления',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $result = $this->productService->deleteProduct($product->id);
        $this->assertTrue($result);
        $this->assertNull(Product::find($product->id));
    }
    public function test_product_service_can_get_all_products()
    {
        Product::create(['name' => 'Продукт 1', 'type' => 'product', 'unit' => 'шт']);
        Product::create(['name' => 'Материал 1', 'type' => 'material', 'unit' => 'кг']);
        $products = $this->productService->getAllProducts();
        $this->assertCount(2, $products);
    }
    public function test_product_service_can_get_products_by_type()
    {
        Product::create(['name' => 'Продукт 1', 'type' => 'product', 'unit' => 'шт']);
        Product::create(['name' => 'Материал 1', 'type' => 'material', 'unit' => 'кг']);
        $products = $this->productService->getProductsByType('product');
        $this->assertCount(1, $products);
        $this->assertEquals('product', $products->first()->type);
    }
    public function test_product_service_can_search_products()
    {
        Product::create(['name' => 'Тестовый продукт', 'type' => 'product', 'unit' => 'шт']);
        Product::create(['name' => 'Другой продукт', 'type' => 'product', 'unit' => 'шт']);
        $products = $this->productService->searchProducts('Тестовый');
        $this->assertCount(1, $products);
        $this->assertStringContainsString('Тестовый', $products->first()->name);
    }
    public function test_product_service_can_get_product_statistics()
    {
        Product::create(['name' => 'Продукт 1', 'type' => 'product', 'unit' => 'шт']);
        Product::create(['name' => 'Материал 1', 'type' => 'material', 'unit' => 'кг']);
        $statistics = $this->productService->getProductStatistics();
        $this->assertArrayHasKey('total_products', $statistics);
        $this->assertArrayHasKey('products_by_type', $statistics);
        $this->assertEquals(2, $statistics['total_products']);
        $this->assertEquals(1, $statistics['products_by_type']['product']);
        $this->assertEquals(1, $statistics['products_by_type']['material']);
    }
    public function test_order_service_can_create_order()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67',
            'email' => 'test@company.com',
            'address' => 'Тестовый адрес'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $dto = new CreateOrderDTO($company->id, $product->id, 100, now()->addDays(7));
        $order = $this->orderService->createOrder($dto);
        $this->assertNotNull($order);
        $this->assertEquals($company->id, $order->company_id);
        $this->assertEquals($product->id, $order->product_id);
        $this->assertEquals(100, $order->quantity);
        $this->assertEquals('wait', $order->status);
    }
    public function test_order_service_can_update_order()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'wait'
        ]);
        $dto = new UpdateOrderDTO(150, now()->addDays(10));
        $result = $this->orderService->updateOrder($order->id, $dto);
        $this->assertTrue($result);
        $order->refresh();
        $this->assertEquals(150, $order->quantity);
    }
    public function test_order_service_can_complete_order()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'in_process'
        ]);
        $result = $this->orderService->completeOrder($order->id);
        $this->assertTrue($result);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }
    public function test_order_service_can_reject_order()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'wait'
        ]);
        $result = $this->orderService->rejectOrder($order->id);
        $this->assertTrue($result);
        $order->refresh();
        $this->assertEquals('rejected', $order->status);
    }
    public function test_order_service_can_get_order_statistics()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'wait'
        ]);
        Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 200,
            'deadline' => now()->addDays(7),
            'status' => 'completed'
        ]);
        $statistics = $this->orderService->getOrderStatistics();
        $this->assertArrayHasKey('total_orders', $statistics);
        $this->assertArrayHasKey('orders_by_status', $statistics);
        $this->assertEquals(2, $statistics['total_orders']);
        $this->assertEquals(1, $statistics['orders_by_status']['wait']);
        $this->assertEquals(1, $statistics['orders_by_status']['completed']);
    }
    public function test_production_task_service_can_create_task()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'wait'
        ]);
        $user = User::create([
            'login' => 'test_user',
            'password' => Hash::make('password123')
        ]);
        $dto = new CreateProductionTaskDTO($order->id, 50, $user->id);
        $task = $this->taskService->createTask($dto);
        $this->assertNotNull($task);
        $this->assertEquals($order->id, $task->order_id);
        $this->assertEquals($user->id, $task->user_id);
        $this->assertEquals(50, $task->quantity);
        $this->assertEquals('wait', $task->status);
    }
    public function test_production_task_service_can_take_task()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'wait'
        ]);
        $user = User::create([
            'login' => 'test_user',
            'password' => Hash::make('password123')
        ]);
        $task = ProductionTask::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'quantity' => 50,
            'status' => 'wait'
        ]);
        $result = $this->taskService->takeTask($task->id, $user->id);
        $this->assertTrue($result);
        $task->refresh();
        $this->assertEquals('in_process', $task->status);
    }
    public function test_production_task_service_can_add_component()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $material = Product::create([
            'name' => 'Тестовый материал',
            'type' => 'material',
            'unit' => 'кг'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'wait'
        ]);
        $user = User::create([
            'login' => 'test_user',
            'password' => Hash::make('password123')
        ]);
        $task = ProductionTask::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'quantity' => 50,
            'status' => 'wait'
        ]);
        $componentDto = new TaskComponentDTO($material->id, 10);
        $component = $this->taskService->addComponent($task->id, $componentDto);
        $this->assertNotNull($component);
        $this->assertEquals($task->id, $component->production_task_id);
        $this->assertEquals($material->id, $component->product_id);
        $this->assertEquals(10, $component->quantity);
    }
    public function test_company_service_can_get_all_companies()
    {
        Company::create([
            'name' => 'Компания 1',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        Company::create([
            'name' => 'Компания 2',
            'contact_person' => 'Петр Петров',
            'phone' => '+7-999-123-45-68'
        ]);
        $companies = $this->companyService->getAllCompanies();
        $this->assertCount(2, $companies);
    }
    public function test_company_service_can_search_companies()
    {
        Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        Company::create([
            'name' => 'Другая компания',
            'contact_person' => 'Петр Петров',
            'phone' => '+7-999-123-45-68'
        ]);
        $companies = $this->companyService->searchCompanies('Тестовая');
        $this->assertCount(1, $companies);
        $this->assertStringContainsString('Тестовая', $companies->first()->name);
    }
    public function test_company_service_can_get_company_statistics()
    {
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'wait'
        ]);
        $statistics = $this->companyService->getCompanyStatistics();
        $this->assertArrayHasKey('total_companies', $statistics);
        $this->assertArrayHasKey('companies_with_orders', $statistics);
        $this->assertEquals(1, $statistics['total_companies']);
        $this->assertEquals(1, $statistics['companies_with_orders']);
    }
    public function test_role_service_can_get_all_roles()
    {
        Role::create(['role' => 'admin', 'description' => 'Администратор']);
        Role::create(['role' => 'manager', 'description' => 'Менеджер']);
        $roles = $this->roleService->getAllRoles();
        $this->assertGreaterThanOrEqual(2, $roles->count());
    }
    public function test_role_service_can_get_role_by_name()
    {
        Role::create(['role' => 'admin', 'description' => 'Администратор']);
        $role = $this->roleService->getRoleByName('admin');
        $this->assertNotNull($role);
        $this->assertEquals('admin', $role->role);
    }
    public function test_role_service_can_get_role_statistics()
    {
        $role = Role::create(['role' => 'admin', 'description' => 'Администратор']);
        $user = User::create([
            'login' => 'admin_user',
            'password' => Hash::make('password123')
        ]);
        $user->syncRoles(['admin']);
        $statistics = $this->roleService->getRoleStatistics();
        $this->assertArrayHasKey('total_roles', $statistics);
        $this->assertArrayHasKey('roles_with_users', $statistics);
        $this->assertGreaterThanOrEqual(1, $statistics['total_roles']);
        $this->assertGreaterThanOrEqual(1, $statistics['roles_with_users']);
    }
    public function test_cache_service_can_remember_and_get()
    {
        $key = 'test_key';
        $value = 'test_value';
        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        }, 60);
        $this->assertEquals($value, $result);
        $cachedValue = $this->cacheService->get($key);
        $this->assertEquals($value, $cachedValue);
    }
    public function test_cache_service_can_put_and_get()
    {
        $key = 'test_put_key';
        $value = 'test_put_value';
        $result = $this->cacheService->put($key, $value, 60);
        $this->assertTrue($result);
        $cachedValue = $this->cacheService->get($key);
        $this->assertEquals($value, $cachedValue);
    }
    public function test_cache_service_can_forget()
    {
        $key = 'test_forget_key';
        $value = 'test_forget_value';
        $this->cacheService->put($key, $value, 60);
        $this->assertTrue($this->cacheService->has($key));
        $result = $this->cacheService->forget($key);
        $this->assertTrue($result);
        $this->assertFalse($this->cacheService->has($key));
    }
    public function test_cache_service_can_work_with_tags()
    {
        $key = 'test_tagged_key';
        $value = 'test_tagged_value';
        $tags = ['test', 'unit'];
        $result = $this->cacheService->rememberWithTags($key, $tags, function () use ($value) {
            return $value;
        }, 60);
        $this->assertEquals($value, $result);
        $cachedValue = $this->cacheService->getWithTags($key, $tags);
        $this->assertEquals($value, $cachedValue);
        $this->assertTrue($this->cacheService->hasWithTags($key, $tags));
    }
    public function test_document_service_can_generate_order_document()
    {
        Storage::fake('local');
        View::shouldReceive('make')->andReturnSelf();
        View::shouldReceive('render')->andReturn('<html>Test Order Document</html>');
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'completed'
        ]);
        $result = $this->documentService->generateOrderDocument($order);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('document_type', $result);
        $this->assertEquals('order', $result['document_type']);
        $this->assertEquals($order->id, $result['order_id']);
    }
    public function test_document_service_can_generate_task_document()
    {
        Storage::fake('local');
        View::shouldReceive('make')->andReturnSelf();
        View::shouldReceive('render')->andReturn('<html>Test Task Document</html>');
        $company = Company::create([
            'name' => 'Тестовая компания',
            'contact_person' => 'Иван Иванов',
            'phone' => '+7-999-123-45-67'
        ]);
        $product = Product::create([
            'name' => 'Тестовый продукт',
            'type' => 'product',
            'unit' => 'шт'
        ]);
        $order = Order::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'deadline' => now()->addDays(7),
            'status' => 'completed'
        ]);
        $user = User::create([
            'login' => 'test_user',
            'password' => Hash::make('password123')
        ]);
        $task = ProductionTask::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'quantity' => 50,
            'status' => 'completed'
        ]);
        $result = $this->documentService->generateTaskDocument($task);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('document_type', $result);
        $this->assertEquals('task', $result['document_type']);
        $this->assertEquals($task->id, $result['task_id']);
    }
    public function test_document_service_can_get_all_documents()
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/orders/test_order.html', 'Test content');
        Storage::disk('local')->put('documents/tasks/test_task.html', 'Test content');
        $documents = $this->documentService->getAllDocuments();
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
    }
    public function test_document_service_can_delete_document()
    {
        Storage::fake('local');
        $filePath = 'documents/orders/test_delete.html';
        Storage::disk('local')->put($filePath, 'Test content');
        $this->assertTrue(Storage::disk('local')->exists($filePath));
        $result = $this->documentService->deleteDocument($filePath);
        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
