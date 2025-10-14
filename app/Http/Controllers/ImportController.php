<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportExcelRequest;
use App\Jobs\ImportExcel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function importProducts(ImportExcelRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $options = $request->getFileOptions();
            $userId = $request->user()->id;
            $fileName = 'import_' . $userId . '_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('temp/imports', $fileName, 'local');
            $fullPath = storage_path('app/' . $filePath);
            $batch = Bus::batch([
                new ImportExcel($fullPath, $userId, $options)
            ])->name("Импорт продукции: {$fileName}")
              ->allowFailures()
              ->dispatch();
            return response()->json([
                'success' => true,
                'message' => 'Файл загружен и импорт запущен в фоновом режиме',
                'data' => [
                    'file_name' => $fileName,
                    'file_size' => $file->getSize(),
                    'options' => $options,
                    'user_id' => $userId,
                    'batch_id' => $batch->id,
                ]
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getImportStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'batch_id' => 'required|string'
            ]);
            $batch = Bus::findBatch($request->batch_id);
            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Батч не найден'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'processed_jobs' => $batch->processedJobs(),
                    'failed_jobs' => $batch->failedJobs,
                    'progress' => $batch->progress(),
                    'finished' => $batch->finished(),
                    'cancelled' => $batch->cancelled(),
                    'created_at' => $batch->createdAt,
                    'finished_at' => $batch->finishedAt,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статуса импорта',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function cancelImport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'batch_id' => 'required|string'
            ]);
            $batch = Bus::findBatch($request->batch_id);
            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Батч не найден'
                ], 404);
            }
            if ($batch->finished()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Импорт уже завершен'
                ], 400);
            }
            $batch->cancel();
            return response()->json([
                'success' => true,
                'message' => 'Импорт отменен',
                'data' => [
                    'batch_id' => $batch->id,
                    'cancelled' => true,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка отмены импорта',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getImportTemplate(): JsonResponse
    {
        try {
            $templateData = [
                ['Название', 'Тип', 'Единица'],
                ['Пример продукта 1', 'product', 'шт'],
                ['Пример материала 1', 'material', 'кг'],
                ['Пример продукта 2', 'product', 'м'],
            ];
            $csvContent = '';
            foreach ($templateData as $row) {
                $csvContent .= implode(',', array_map(function($cell) {
                    return '"' . str_replace('"', '""', $cell) . '"';
                }, $row)) . "\n";
            }
            $fileName = 'import_template_' . time() . '.csv';
            $filePath = storage_path('app/temp/' . $fileName);
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
            file_put_contents($filePath, $csvContent);
            return response()->json([
                'success' => true,
                'message' => 'Шаблон для импорта создан',
                'data' => [
                    'template_url' => route('import.download-template', ['file' => $fileName]),
                    'file_name' => $fileName,
                    'instructions' => [
                        '1. Скачайте шаблон',
                        '2. Заполните данные в соответствии с колонками',
                        '3. Сохраните файл в формате Excel (.xlsx) или CSV',
                        '4. Загрузите файл через API импорта',
                    ],
                    'columns' => [
                        'Название' => 'Обязательное поле. Название продукта или материала',
                        'Тип' => 'Обязательное поле. product (продукт) или material (материал)',
                        'Единица' => 'Обязательное поле. Единица измерения (шт, кг, м, л и т.д.)',
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания шаблона',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function downloadTemplate(Request $request)
    {
        try {
            $fileName = $request->query('file');
            if (!$fileName || !preg_match('/^import_template_\d+\.csv$/', $fileName)) {
                abort(404, 'Файл не найден');
            }
            $filePath = storage_path('app/temp/' . $fileName);
            if (!file_exists($filePath)) {
                abort(404, 'Файл не найден');
            }
            return response()->download($filePath, 'import_template.csv', [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="import_template.csv"',
            ]);
        } catch (\Exception $e) {
            abort(500, 'Ошибка загрузки файла');
        }
    }
    public function validateFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);
            $file = $request->file('file');
            $validationResults = $this->validateExcelStructure($file);

            return response()->json([
                'success' => true,
                'message' => 'Файл прошел предварительную валидацию',
                'data' => $validationResults
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации файла',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    protected function validateExcelStructure($file): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $headers = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $headers[] = $worksheet->getCell($col . '1')->getValue();
            }
            $requiredHeaders = ['Название', 'Тип', 'Единица'];
            $foundHeaders = array_intersect($requiredHeaders, $headers);
            return [
                'total_rows' => $highestRow - 1,
                'total_columns' => ord($highestColumn) - ord('A') + 1,
                'headers' => $headers,
                'required_headers_found' => count($foundHeaders),
                'required_headers_missing' => array_diff($requiredHeaders, $foundHeaders),
                'file_size' => $file->getSize(),
                'file_name' => $file->getClientOriginalName(),
            ];
        } catch (\Exception $e) {
            throw new \Exception('Не удалось прочитать файл: ' . $e->getMessage());
        }
    }
}
