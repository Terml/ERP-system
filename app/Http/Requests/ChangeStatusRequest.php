<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\StatusRule;
use App\Models\Order;
use App\Models\ProductionTask;

class ChangeStatusRequest extends BaseRequest
{
    protected string $entityType;
    protected ?int $entityId;
    public function __construct()
    {
        parent::__construct();
        $this->entityType = $this->determineEntityType();
        $this->entityId = $this->getEntityId();
    }
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyRole(['manager', 'dispatcher', 'master', 'otk']);
    }
    public function rules(): array
    {
        $currentStatus = $this->getCurrentStatus();
        return [
            'status' => [
                'required',
                'string',
                new StatusRule($this->entityType, $currentStatus, $this->entityId)
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'status.required' => 'Статус обязателен для заполнения',
            'status.string' => 'Статус должен быть строкой',
        ];
    }
    public function attributes(): array
    {
        return [
            'status' => 'статус',
        ];
    }
    protected function determineEntityType(): string
    {
        $route = $this->route();
        if (str_contains($route->getName() ?? '', 'order')) {
            return 'order';
        } elseif (str_contains($route->getName() ?? '', 'task')) {
            return 'task';
        }
        if ($this->route('orderId')) {
            return 'order';
        } elseif ($this->route('taskId')) {
            return 'task';
        }
        return 'order';
    }
    protected function getEntityId(): ?int
    {
        return $this->route('orderId') ?? $this->route('taskId');
    }
    protected function getCurrentStatus(): ?string
    {
        if (!$this->entityId) {
            return null;
        }
        if ($this->entityType === 'order') {
            $order = Order::find($this->entityId);
            return $order ? $order->status : null;
        } elseif ($this->entityType === 'task') {
            $task = ProductionTask::find($this->entityId);
            return $task ? $task->status : null;
        }
        return null;
    }
}
