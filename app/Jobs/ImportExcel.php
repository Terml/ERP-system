<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ProductService;
use App\Services\CacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;
    protected string $filePath;
    protected int $userId;
    protected array $options;
    public int $tries = 2;
    public int $timeout = 600;
    public int $backoff = 60;
    protected int $chunkSize = 1000;
    public function __construct(string $filePath, int $userId, array $options = [])
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->options = $options;
        if (isset($options['chunk_size']) && is_numeric($options['chunk_size'])) {
            $this->chunkSize = (int) $options['chunk_size'];
        }
    }
    public function handle(ProductService $productService, CacheService $cacheService): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            Log::info("Импорт отменен для файла: {$this->filePath}");
            return;
        }
        $startTime = microtime(true);
        $results = [
            'total_rows' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'processing_time' => 0,
            'chunks_processed' => 0,
            'chunk_size' => $this->chunkSize,
        ];
        try {
            Log::info("Начало импорта Excel файла: {$this->filePath}, размер чанка: {$this->chunkSize}");
            $spreadsheet = IOFactory::load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $results['total_rows'] = $highestRow - 1; // удаление заголовка
            $columns = $this->getColumnMapping($worksheet);
            $this->processInChunks($worksheet, $columns, $results, $productService);
            $cacheService->flushPattern('products:*');
            $results['processing_time'] = round(microtime(true) - $startTime, 2);
            Log::info("Импорт завершен", $results);
        } catch (\Exception $e) {
            Log::error("Критическая ошибка импорта: " . $e->getMessage());
            $results['errors'][] = [
                'error' => 'Критическая ошибка: ' . $e->getMessage(),
            ];
        } finally {
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
        }
        $this->notifyUser($results);
    }
    protected function processInChunks($worksheet, array $columns, array &$results, ProductService $productService): void
    {
        $highestRow = $worksheet->getHighestRow();
        $totalChunks = ceil(($highestRow - 1) / $this->chunkSize);
        Log::info("Начинаем обработку {$totalChunks} чанков по {$this->chunkSize} строк");
        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            if ($this->batch() && $this->batch()->cancelled()) {
                Log::info("Импорт отменен на чанке " . ($chunkIndex + 1) . "/{$totalChunks}");
                break;
            }
            $startRow = 2 + ($chunkIndex * $this->chunkSize);
            $endRow = min($startRow + $this->chunkSize - 1, $highestRow);
            Log::info("Обработка чанка " . ($chunkIndex + 1) . "/{$totalChunks} (строки {$startRow}-{$endRow})");
            try {
                // обработка чанка
                DB::transaction(function () use ($worksheet, $columns, $startRow, $endRow, &$results, $productService) {
                    $this->processChunk($worksheet, $columns, $startRow, $endRow, $results, $productService);
                });
                $results['chunks_processed']++;
            } catch (\Exception $e) {
                Log::error("Ошибка обработки чанка " . ($chunkIndex + 1) . ": " . $e->getMessage());
                $results['errors'][] = [
                    'chunk' => $chunkIndex + 1,
                    'error' => 'Ошибка чанка: ' . $e->getMessage(),
                ];
            }
        }
    }
    protected function processChunk($worksheet, array $columns, int $startRow, int $endRow, array &$results, ProductService $productService): void
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            try {
                $rowData = $this->extractRowData($worksheet, $row, $columns);
                if ($this->isEmptyRow($rowData)) {
                    $results['skipped']++;
                    continue;
                }
                $validation = $this->validateRowData($rowData);
                if ($validation->fails()) {
                    $results['errors'][] = [
                        'row' => $row,
                        'data' => $rowData,
                        'errors' => $validation->errors()->toArray(),
                    ];
                    $results['skipped']++;
                    continue;
                }
                $product = $this->createProduct($rowData, $productService);
                if ($product) {
                    $results['imported']++;
                    Log::debug("Импортирован продукт: {$product->name} (ID: {$product->id})");
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ];
                $results['skipped']++;
                Log::error("Ошибка обработки строки {$row}: " . $e->getMessage());
            }
        }
    }
    protected function getColumnMapping($worksheet): array
    {
        $headerRow = 1;
        $columns = [];
        $highestColumn = $worksheet->getHighestColumn();
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $headerValue = $worksheet->getCell($col . $headerRow)->getValue();
            $headerValue = trim(strtolower($headerValue));
            switch ($headerValue) {
                case 'название':
                case 'name':
                case 'наименование':
                    $columns['name'] = $col;
                    break;
                case 'тип':
                case 'type':
                case 'вид':
                    $columns['type'] = $col;
                    break;
                case 'единица':
                case 'unit':
                case 'ед.изм':
                case 'единица измерения':
                    $columns['unit'] = $col;
                    break;
                case 'описание':
                case 'description':
                case 'опис':
                    $columns['description'] = $col;
                    break;
                case 'цена':
                case 'price':
                case 'стоимость':
                    $columns['price'] = $col;
                    break;
            }
        }
        return $columns;
    }
    protected function extractRowData($worksheet, int $row, array $columns): array
    {
        $data = [];
        foreach ($columns as $field => $column) {
            $value = $worksheet->getCell($column . $row)->getValue();

            if ($field === 'created_at' || $field === 'updated_at') {
                if (is_numeric($value)) {
                    $value = Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
                }
            }
            $data[$field] = $value;
        }
        return $data;
    }
    protected function isEmptyRow(array $data): bool
    {
        return empty(array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        }));
    }
    protected function validateRowData(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:product,material',
            'unit' => 'required|string|max:50',
        ];
        $messages = [
            'name.required' => 'Название продукта обязательно',
            'name.max' => 'Название продукта не должно превышать 255 символов',
            'type.required' => 'Тип продукта обязателен',
            'type.in' => 'Тип продукта должен быть: product или material',
            'unit.required' => 'Единица измерения обязательна',
            'unit.max' => 'Единица измерения не должна превышать 50 символов',
        ];
        return Validator::make($data, $rules, $messages);
    }

    protected function createProduct(array $data, ProductService $productService): ?Product
    {
        try {
            return DB::transaction(function () use ($data, $productService) {
                $existingProduct = Product::where('name', $data['name'])->lockForUpdate()->first();
                if ($existingProduct) {
                    if ($this->options['overwrite_existing'] ?? false) {
                        Log::info("Перезаписываем существующий продукт: {$data['name']}");
                        $existingProduct->update([
                            'type' => trim($data['type']),
                            'unit' => trim($data['unit']),
                            'description' => isset($data['description']) ? trim($data['description']) : null,
                            'price' => isset($data['price']) ? (float)$data['price'] : null,
                        ]);
                        return $existingProduct;
                    } elseif ($this->options['skip_duplicates'] ?? true) {
                        Log::warning("Пропускаем дубликат продукта: {$data['name']}");
                        return null;
                    } else {
                        throw new \Exception("Продукт с названием '{$data['name']}' уже существует");
                    }
                }
                $product = Product::create([
                    'name' => trim($data['name']),
                    'type' => trim($data['type']),
                    'unit' => trim($data['unit']),
                    'description' => isset($data['description']) ? trim($data['description']) : null,
                    'price' => isset($data['price']) ? (float)$data['price'] : null,
                ]);
                return $product;
            });
        } catch (\Exception $e) {
            Log::error("Ошибка создания продукта: " . $e->getMessage());
            return null;
        }
    }
    protected function notifyUser(array $results): void
    {
        Log::info("Результаты импорта для пользователя {$this->userId}:", $results);
    }
    public function backoff(): array
    {
        return [60, 120]; // 1 мин, 2 мин
    }
    public function retryUntil(): \DateTime
    {
        return now()->addHours(3);
    }
    public function failed(\Throwable $exception): void
    {
        Log::error("ImportExcel job failed after {$this->tries} attempts: " . $exception->getMessage(), [
            'file_path' => $this->filePath,
            'user_id' => $this->userId,
            'options' => $this->options,
            'exception' => $exception->getTraceAsString()
        ]);
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}