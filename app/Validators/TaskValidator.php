<?php
declare(strict_types=1);

namespace App\Validators;

class TaskValidator extends Validator
{
    public const VALID_PRIORITIES = ['Critical', 'Urgent', 'High', 'Normal', 'Medium', 'Low'];
    public const VALID_STATUSES = ['Pending', 'In Progress', 'Completed', 'Overdue', 'Cancelled'];

    public function validate(array $data): bool
    {
        $this->validateRequired($data, [
            'task_title' => 'Task Title',
            'assigned_to' => 'Assigned Officer',
            'due_date' => 'Due Date'
        ]);

        if (!empty($data['due_date'])) {
            $this->validateDate($data['due_date'], 'due_date', 'Due Date');
        }

        if (!empty($data['priority']) && !in_array($data['priority'], self::VALID_PRIORITIES, true)) {
            $this->addError('priority', 'Invalid priority selected.');
        }

        if (!empty($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true)) {
            $this->addError('status', 'Invalid task status selected.');
        }

        return $this->isValid();
    }
}
