<?php

namespace App\Factories;

use App\DTOs\CreateProductionTaskDTO;
use App\DTOs\CheckingDTO;
use App\DTOs\TaskComponentDTO;
use App\Http\Requests\CheckingOrderRequest;
use Illuminate\Http\Request;

class ProductionTaskDTOFactory
{
    public static function createFromRequest(Request $request): CreateProductionTaskDTO
    {
        $data = $request->all();
        
        return new CreateProductionTaskDTO(
            orderId: $data['order_id'],
            quantity: $data['quantity'],
            userId: $data['user_id'] ?? null
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

    public static function createTaskComponentFromRequest(Request $request): TaskComponentDTO
    {
        $data = $request->all();
        
        return new TaskComponentDTO(
            productId: $data['product_id'],
            quantity: $data['quantity']
        );
    }
}