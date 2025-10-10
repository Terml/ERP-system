<?php

namespace App\DTOs;

class CheckingDTO
{
    public function __construct(
        public readonly ?string $inspectionNotes = null,
        public readonly ?int $qualitySelfAssessment = null,
        public readonly ?int $completionPercentage = null,
        public readonly ?array $materialsUsed = null,
        public readonly ?string $issuesEncountered = null,
        public readonly ?int $estimatedCompletionTime = null
    ) {}
    public function toArray(): array
    {
        return array_filter([
            'inspection_notes' => $this->inspectionNotes,
            'quality_self_assessment' => $this->qualitySelfAssessment,
            'completion_percentage' => $this->completionPercentage,
            'materials_used' => $this->materialsUsed,
            'issues_encountered' => $this->issuesEncountered,
            'estimated_completion_time' => $this->estimatedCompletionTime,
        ], fn($value) => $value !== null);
    }
}