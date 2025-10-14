<?php

namespace App\Factories;

use App\DTOs\CreateProductionTaskDTO;
use App\DTOs\CheckingDTO;
use App\DTOs\TaskComponentDTO;
use App\Http\Requests\CheckingOrderRequest;
use App\Http\Requests\CreateProductionTaskRequest;
use App\Http\Requests\CreateProductionTaskWithComponentsRequest;
use App\Http\Requests\AddComponentRequest;
use Illuminate\Http\Request;

class ProductionTaskDTOFactory
{
    public static function createFromRequest(CreateProductionTaskRequest $request): CreateProductionTaskDTO
    {
        $validated = $request->validated();
        
        return new CreateProductionTaskDTO(
            orderId: $validated['order_id'],
            quantity: $validated['quantity'],
            userId: $validated['user_id'] ?? null
        );
    }

    public static function createFromRequestWithComponents(CreateProductionTaskWithComponentsRequest $request): CreateProductionTaskDTO
    {
        $validated = $request->validated();
        return new CreateProductionTaskDTO(
            orderId: $validated['order_id'],
            userId: $validated['user_id'] ?? null
        );
    }
    public static function createSendForInspectionFromRequest(CheckingOrderRequest $request): CheckingDTO
    {
        $validated = $request->validated();
        return new CheckingDTO(
            inspectionNotes: $validated['inspection_notes'] ?? null,
            qualitySelfAssessment: $validated['quality_self_assessment'] ?? null,
            completionPercentage: $validated['completion_percentage'] ?? null,
            materialsUsed: $validated['materials_used'] ?? null,
            issuesEncountered: $validated['issues_encountered'] ?? null,
            estimatedCompletionTime: $validated['estimated_completion_time'] ?? null
        );
    }

    public static function createTaskComponentFromRequest(AddComponentRequest $request): TaskComponentDTO
    {
        $validated = $request->validated();
        
        return new TaskComponentDTO(
            productId: $validated['product_id'],
            quantity: $validated['quantity']
        );
    }
}