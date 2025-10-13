<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Order;
use App\Models\ProductionTask;

class StatusRule implements ValidationRule
{
    protected string $type;
    protected ?string $currentStatus;
    protected ?int $entityId;
    public function __construct(string $type, ?string $currentStatus = null, ?int $entityId = null)
    {
        $this->type = $type;
        $this->currentStatus = $currentStatus;
        $this->entityId = $entityId;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->type === 'order') {
            $this->validateOrderStatus($value, $fail);
        } elseif ($this->type === 'task') {
            $this->validateTaskStatus($value, $fail);
        } else {
            $fail('Неизвестный тип сущности для валидации статуса.');
        }
    }
    protected function validateOrderStatus(mixed $value, Closure $fail): void
    {
        $validStatuses = ['wait', 'in_process', 'completed', 'rejected'];
        if (!in_array($value, $validStatuses)) {
            $fail('Недопустимый статус заказа. Допустимые статусы: ' . implode(', ', $validStatuses) . '.');
            return;
        }
        if ($this->currentStatus === null) {
            return;
        }
        $allowedTransitions = [
            'wait' => ['in_process', 'rejected'],
            'in_process' => ['completed', 'rejected'],
            'completed' => [], 
            'rejected' => ['wait'],
        ];
        if (!isset($allowedTransitions[$this->currentStatus])) {
            $fail('Неизвестный текущий статус заказа.');
            return;
        }
        if (!in_array($value, $allowedTransitions[$this->currentStatus])) {
            $allowedStatuses = implode(', ', $allowedTransitions[$this->currentStatus]);
            $fail("Невозможно изменить статус заказа с '{$this->currentStatus}' на '{$value}'. Допустимые переходы: {$allowedStatuses}.");
            return;
        }
        if ($value === 'completed' && $this->currentStatus === 'in_process') {
            if ($this->entityId) {
                $incompleteTasks = ProductionTask::where('order_id', $this->entityId)
                    ->where('status', '!=', 'completed')
                    ->exists();
                if ($incompleteTasks) {
                    $fail('Нельзя завершить заказ, пока не завершены все производственные задания.');
                    return;
                }
            }
        }
    }
    protected function validateTaskStatus(mixed $value, Closure $fail): void
    {
        $validStatuses = ['wait', 'in_process', 'checking', 'completed', 'rejected'];
        if (!in_array($value, $validStatuses)) {
            $fail('Недопустимый статус задания. Допустимые статусы: ' . implode(', ', $validStatuses) . '.');
            return;
        }
        if ($this->currentStatus === null) {
            return;
        }
        $allowedTransitions = [
            'wait' => ['in_process', 'rejected'],
            'in_process' => ['checking', 'rejected'],
            'checking' => ['completed', 'rejected', 'in_process'], 
            'completed' => [],
            'rejected' => ['wait'],
        ];
        if (!isset($allowedTransitions[$this->currentStatus])) {
            $fail('Неизвестный текущий статус задания.');
            return;
        }
        if (!in_array($value, $allowedTransitions[$this->currentStatus])) {
            $allowedStatuses = implode(', ', $allowedTransitions[$this->currentStatus]);
            $fail("Невозможно изменить статус задания с '{$this->currentStatus}' на '{$value}'. Допустимые переходы: {$allowedStatuses}.");
            return;
        }
        if ($value === 'in_process' && $this->currentStatus === 'wait') {
            if ($this->entityId) {
                $task = ProductionTask::find($this->entityId);
                if ($task && !$task->user_id) {
                    $fail('Нельзя взять задание в работу без назначения мастера.');
                    return;
                }
            }
        }
        if ($value === 'checking' && $this->currentStatus === 'in_process') {
            if ($this->entityId) {
                $task = ProductionTask::find($this->entityId);
                if ($task && !$task->user_id) {
                    $fail('Нельзя отправить задание на проверку без назначения мастера.');
                    return;
                }
            }
        }

        if ($value === 'completed' && $this->currentStatus === 'checking') {
            if ($this->entityId) {
                $task = ProductionTask::find($this->entityId);
                if ($task && !$task->user_id) {
                    $fail('Нельзя завершить задание без назначения мастера.');
                    return;
                }
            }
        }
        if ($value === 'rejected' && $this->currentStatus === 'checking') {
            if ($this->entityId) {
                $task = ProductionTask::find($this->entityId);
                if ($task && !$task->user_id) {
                    $fail('Нельзя отклонить задание без назначения мастера.');
                    return;
                }
            }
        }
    }
}
