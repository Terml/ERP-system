<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NoticeManager implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected Order $order;
    protected string $eventType;
    protected array $additionalData;
    protected ?int $specificManagerId;
    public int $tries = 5;
    public int $timeout = 120;
    public int $backoff = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, string $eventType, array $additionalData = [], ?int $specificManagerId = null)
    {
        $this->order = $order;
        $this->eventType = $eventType;
        $this->additionalData = $additionalData;
        $this->specificManagerId = $specificManagerId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $managers = $this->getManagersToNotify();
            if ($managers->isEmpty()) {
                Log::warning("Не найдено менеджеров для уведомления о заказе #{$this->order->id}");
                return;
            }
            foreach ($managers as $manager) {
                $this->sendNotificationToManager($manager);
            }
        } catch (\Exception $e) {
            Log::error("Ошибка сохранения уведомления о заказе #{$this->order->id}: " . $e->getMessage());
            throw $e;
        }
    }
    protected function getManagersToNotify()
    {
        $query = User::whereHas('roles', function ($query) {
            $query->where('role', 'manager');
        });
        if ($this->specificManagerId) {
            $query->where('id', $this->specificManagerId);
        }
        return $query->get();
    }

    protected function sendNotificationToManager(User $manager): void
    {
        try {
            $notificationData = $this->prepareNotificationData($manager);
            $this->saveNotification($manager, $notificationData);
        } catch (\Exception $e) {
            Log::error("Ошибка сохранения уведомления для менеджера {$manager->login}: " . $e->getMessage());
        }
    }
    protected function prepareNotificationData(User $manager): array
    {
        $order = $this->order->load(['company']);
        return [
            'manager' => $manager,
            'order' => $order,
            'event_type' => $this->eventType,
            'event_title' => $this->getEventTitle(),
            'event_message' => $this->getEventMessage(),
            'additional_data' => $this->additionalData,
            'timestamp' => now(),
        ];
    }
    protected function getEventTitle(): string
    {
        return match ($this->eventType) {
            'created' => 'Новый заказ создан',
            'updated' => 'Заказ обновлен',
            'status_changed' => 'Изменен статус заказа',
            'completed' => 'Заказ завершен',
            'rejected' => 'Заказ отклонен',
            'deadline_approaching' => 'Приближается срок выполнения заказа',
            'deadline_expired' => 'Просрочен срок выполнения заказа',
            default => 'Уведомление о заказе',
        };
    }
    protected function getEventMessage(): string
    {
        $order = $this->order;
        $companyName = $order->company->name ?? 'Неизвестная компания';
        return match ($this->eventType) {
            'created' => "Создан новый заказ #{$order->id} от компании '{$companyName}'. Срок выполнения: {$order->deadline}.",
            'updated' => "Заказ #{$order->id} от компании '{$companyName}' был обновлен. Текущий статус: {$this->getStatusText($order->status)}.",
            'status_changed' => "Изменен статус заказа #{$order->id} от компании '{$companyName}' на '{$this->getStatusText($order->status)}'.",
            'completed' => "Заказ #{$order->id} от компании '{$companyName}' успешно завершен и принят ОТК.",
            'rejected' => "Заказ #{$order->id} от компании '{$companyName}' был отклонен ОТК.",
            'deadline_expired' => "Просрочен срок выполнения заказа #{$order->id} от компании '{$companyName}'. Дедлайн был: {$order->deadline}.",
            default => "Уведомление о заказе #{$order->id} от компании '{$companyName}'.",
        };
    }
    protected function getStatusText(string $status): string
    {
        return match ($status) {
            'wait' => 'Ожидание',
            'in_process' => 'В процессе',
            'completed' => 'Завершен',
            'rejected' => 'Отклонен',
            default => $status,
        };
    }
    protected function saveNotification(User $manager, array $data): void
    {
        try {
            $manager->notifications()->create([
                'type' => 'order_notification',
                'data' => [
                    'type' => $this->eventType,
                    'order_id' => $this->order->id,
                    'company_name' => $this->order->company->name ?? 'Неизвестная компания',
                    'deadline' => $this->order->deadline,
                    'status' => $this->order->status,
                    'title' => $data['event_title'],
                    'message' => $data['event_message'],
                    'additional_data' => $this->additionalData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Ошибка сохранения уведомления в базу данных: " . $e->getMessage());
        }
    }
    protected function getPriority(): string
    {
        return match ($this->eventType) {
            'deadline_expired' => 'high',
            'deadline_approaching' => 'medium',
            'rejected' => 'medium',
            'completed' => 'low',
            default => 'low',
        };
    }
    public function backoff(): array
    {
        return [15, 30, 60, 120]; // 15 сек, 30 сек, 1 мин, 2 мин
    }
    public function retryUntil(): \DateTime
    {
        return now()->addHours(1); // Повторять в течение 1 часа
    }
    public function failed(\Throwable $exception): void
    {
        Log::error("Job NoticeManager failed after {$this->tries} attempts для заказа #{$this->order->id}: " . $exception->getMessage(), [
            'order_id' => $this->order->id,
            'event_type' => $this->eventType,
            'specific_manager_id' => $this->specificManagerId,
            'exception' => $exception->getTraceAsString()
        ]);
    }
}
