<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductionTask;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocumentService
{
    public function generateOrderDocument(Order $order): array
    {
        if ($order->status !== 'completed') {
            throw new \Exception('Печать доступна только для завершенных заказов');
        }
        $order->load(['company', 'product']);
        $data = [
            'order' => $order,
            'company' => $order->company,
            'product' => $order->product,
            'generated_at' => now(),
            'document_type' => 'order',
        ];
        $html = View::make('documents.order', $data)->render();
        $fileName = $this->generateFileName('order', $order->id);
        $filePath = $this->saveDocument($fileName, $html, 'orders');
        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'download_url' => $this->getDownloadUrl($filePath),
            'document_type' => 'order',
            'order_id' => $order->id,
            'generated_at' => now(),
        ];
    }
    public function generateTaskDocument(ProductionTask $task): array
    {
        if ($task->status !== 'completed') {
            throw new \Exception('Печать доступна только для завершенных заданий');
        }
        $task->load(['order.company', 'order.product', 'user', 'components']);
        $data = [
            'task' => $task,
            'order' => $task->order,
            'company' => $task->order->company,
            'product' => $task->order->product,
            'master' => $task->user,
            'components' => $task->components,
            'generated_at' => now(),
            'document_type' => 'task',
        ];
        $html = View::make('documents.task', $data)->render();
        $fileName = $this->generateFileName('task', $task->id);
        $filePath = $this->saveDocument($fileName, $html, 'tasks');
        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'download_url' => $this->getDownloadUrl($filePath),
            'document_type' => 'task',
            'task_id' => $task->id,
            'generated_at' => now(),
        ];
    }
    public function getAllDocuments(): array
    {
        $documents = [];
        $orderFiles = Storage::disk('local')->files('documents/orders');
        foreach ($orderFiles as $file) {
            $documents[] = $this->getDocumentInfo($file, 'order');
        }
        $taskFiles = Storage::disk('local')->files('documents/tasks');
        foreach ($taskFiles as $file) {
            $documents[] = $this->getDocumentInfo($file, 'task');
        }
        usort($documents, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        return $documents;
    }
    public function getDocumentInfo(string $filePath, string $type): array
    {
        $fileName = basename($filePath);
        $fileSize = Storage::disk('local')->size($filePath);
        $createdAt = Carbon::createFromTimestamp(Storage::disk('local')->lastModified($filePath));
        $id = $this->extractIdFromFileName($fileName);
        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_size_formatted' => $this->formatFileSize($fileSize),
            'download_url' => $this->getDownloadUrl($filePath),
            'document_type' => $type,
            'entity_id' => $id,
            'created_at' => $createdAt,
        ];
    }
    public function deleteDocument(string $filePath): bool
    {
        if (Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->delete($filePath);
        }
        return false;
    }
    public function getDocumentContent(string $filePath): string
    {
        if (!Storage::disk('local')->exists($filePath)) {
            throw new \Exception('Документ не найден');
        }
        return Storage::disk('local')->get($filePath);
    }
    protected function generateFileName(string $type, int $id): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "{$type}_{$id}_{$timestamp}.html";
    }
    protected function saveDocument(string $fileName, string $content, string $subfolder): string
    {
        $filePath = "documents/{$subfolder}/{$fileName}";
        $directory = dirname($filePath);
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }
        Storage::disk('local')->put($filePath, $content);
        return $filePath;
    }
    protected function getDownloadUrl(string $filePath): string
    {
        return route('documents.download', ['file' => $filePath]);
    }
    protected function extractIdFromFileName(string $fileName): ?int
    {
        if (preg_match('/^(\w+)_(\d+)_/', $fileName, $matches)) {
            return (int) $matches[2];
        }
        return null;
    }
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
