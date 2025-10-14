<?php

namespace App\Http\Controllers;

use App\Jobs\ReportProduction;
use App\Models\Company;
use App\Models\Product;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function generateCompanyReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }
            $parameters = [
                'company_id' => $request->get('company_id')
            ];
            $delay = $request->get('delay_minutes', 0);
            if ($delay > 0) {
                ReportProduction::dispatch('by_company', $parameters, $request->user()?->id)
                    ->delay(now()->addMinutes($delay));
            } else {
                ReportProduction::dispatch('by_company', $parameters, $request->user()?->id);
            }
            return response()->json([
                'success' => true,
                'message' => 'Отчет по компании поставлен в очередь на генерацию',
                'data' => [
                    'report_type' => 'by_company',
                    'company_id' => $parameters['company_id'],
                    'status' => 'queued'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка генерации отчета по компании',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function generateProductReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|integer|exists:products,id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }
            $parameters = [
                'product_id' => $request->get('product_id')
            ];
            $delay = $request->get('delay_minutes', 0);
            if ($delay > 0) {
                ReportProduction::dispatch('by_product', $parameters, $request->user()?->id)
                    ->delay(now()->addMinutes($delay));
            } else {
                ReportProduction::dispatch('by_product', $parameters, $request->user()?->id);
            }
            return response()->json([
                'success' => true,
                'message' => 'Отчет по продукту поставлен в очередь на генерацию',
                'data' => [
                    'report_type' => 'by_product',
                    'product_id' => $parameters['product_id'],
                    'status' => 'queued'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка генерации отчета по продукту',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $delay = $request->get('delay_minutes', 0);
            if ($delay > 0) {
                ReportProduction::dispatch('statistics', [], $request->user()?->id)
                    ->delay(now()->addMinutes($delay));
            } else {
                ReportProduction::dispatch('statistics', [], $request->user()?->id);
            }
            return response()->json([
                'success' => true,
                'message' => 'Отчет поставлен в очередь на генерацию',
                'data' => [
                    'report_type' => 'statistics',
                    'status' => 'expired'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка генерации отчета',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getReportContent(Request $request): JsonResponse
    {
        try {
            $reportId = $request->get('report_id');
            if (!$reportId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID отчета не указан'
                ], 400);
            }
            $report = Report::findOrFail($reportId);
            if (!$report->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Отчет еще не готов'
                ], 400);
            }
            return response()->json([
                'success' => true,
                'data' => $report->data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения содержимого отчета',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function deleteReport(Request $request): JsonResponse
    {
        try {
            $reportId = $request->get('report_id');
            if (!$reportId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID отчета не указан'
                ], 400);
            }
            $report = Report::findOrFail($reportId);
            $report->delete();
            return response()->json([
                'success' => true,
                'message' => 'Отчет успешно удален'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления отчета',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getReports(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $type = $request->get('type');
            $userId = $request->get('user_id');
            $query = Report::with('user');
            if ($type) {
                $query->where('type', $type);
            }
            if ($userId) {
                $query->where('user_id', $userId);
            }
            $reports = $query->orderBy('created_at', 'desc')->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения списка отчетов',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getReport(Request $request, int $id): JsonResponse
    {
        try {
            $report = Report::with('user')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Отчет не найден',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    public function getReportsStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_reports' => Report::count(),
                'completed_reports' => Report::completed()->count(),
                'reports_by_type' => Report::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type'),
                'recent_reports' => Report::with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($report) {
                        return [
                            'id' => $report->id,
                            'type' => $report->type,
                            'user_name' => $report->user->login ?? 'Неизвестно',
                            'created_at' => $report->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
            ];
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики отчетов',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}