<?php

namespace App\Http\Controllers;

use App\Models\MatchResult;
use App\Models\UploadBatch;
use App\Services\MatchAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ResultsController extends Controller
{
    protected MatchAnalyticsService $matchAnalyticsService;

    public function __construct(MatchAnalyticsService $matchAnalyticsService)
    {
        $this->matchAnalyticsService = $matchAnalyticsService;
    }

    public function index(Request $request)
    {
        $query = MatchResult::query()->with([
            'batch',
            'matchedRecord.originBatch',
            'matchedRecord.templateFieldValues.templateField',
            'matchedRecord.templateFieldValues.batch'
        ]);
        
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('match_status', $request->status);
        }
        
        $results = $query->orderBy('created_at', 'desc')->paginate(20);
        $batches = UploadBatch::orderBy('id', 'desc')->get();
        
        // Calculate statistics for the current batch if filtering by batch_id
        $batchStats = null;
        $columnMapping = null;
        $isFromUpload = session()->has('column_mapping');
        
        if ($request->filled('batch_id')) {
            $batch = UploadBatch::find($request->batch_id);
            
            if ($batch) {
                $batchStats = [
                    'total_rows' => MatchResult::where('batch_id', $batch->id)->count(),
                    'new_records' => MatchResult::where('batch_id', $batch->id)->where('match_status', 'NEW RECORD')->count(),
                    'matched' => MatchResult::where('batch_id', $batch->id)->where('match_status', 'MATCHED')->count(),
                    'possible_duplicates' => MatchResult::where('batch_id', $batch->id)->where('match_status', 'POSSIBLE DUPLICATE')->count(),
                ];
                
                // Get column mapping from session (if just uploaded) or from batch record
                $columnMapping = session('column_mapping', $batch->column_mapping);
            }
        }
        
        return view('pages.results', compact('results', 'batches', 'batchStats', 'columnMapping', 'isFromUpload'));
    }

    /**
     * Get analytics data for batch
     * 
     * @param int $batchId
     * @return JsonResponse
     */
    public function getBatchAnalytics(int $batchId): JsonResponse
    {
        try {
            $batch = UploadBatch::find($batchId);
            
            if (!$batch) {
                Log::warning('Analytics requested for non-existent batch', ['batch_id' => $batchId]);
                return response()->json([
                    'error' => 'Batch not found',
                    'message' => 'The requested batch does not exist.'
                ], 404);
            }

            // Get column mapping from session if available
            $columnMapping = session('column_mapping', []);

            $statistics = $this->matchAnalyticsService->calculateBatchStatistics($batchId);
            $fieldPopulation = $this->matchAnalyticsService->calculateFieldPopulationRates($batchId);
            $chartData = $this->matchAnalyticsService->generateChartData($batchId, $columnMapping);
            $quality = $this->matchAnalyticsService->calculateQualityScore($statistics);

            return response()->json([
                'batch_id' => $batchId,
                'statistics' => $statistics,
                'quality' => $quality,
                'field_population' => $fieldPopulation,
                'chart_data' => $chartData,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error calculating batch statistics', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Database error',
                'message' => 'Unable to calculate statistics. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error calculating batch analytics', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Server error',
                'message' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Get detailed field breakdown for match result
     * 
     * @param int $resultId
     * @return JsonResponse
     */
    public function getFieldBreakdown(int $resultId): JsonResponse
    {
        try {
            $result = MatchResult::find($resultId);
            
            if (!$result) {
                Log::warning('Field breakdown requested for non-existent result', ['result_id' => $resultId]);
                return response()->json([
                    'error' => 'Result not found',
                    'message' => 'The requested match result does not exist.'
                ], 404);
            }

            $fieldBreakdown = $result->field_breakdown;
            
            if (!$fieldBreakdown || empty($fieldBreakdown)) {
                return response()->json([
                    'error' => 'No data',
                    'message' => 'No field breakdown data available for this match result.'
                ], 404);
            }

            return response()->json($fieldBreakdown);
        } catch (\Exception $e) {
            Log::error('Error fetching field breakdown', [
                'result_id' => $resultId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch field breakdown. Please try again.'
            ], 500);
        }
    }

    /**
     * Export field breakdown as CSV
     * 
     * @param int $resultId
     * @return Response
     */
    public function exportFieldBreakdown(int $resultId): Response
    {
        try {
            $result = MatchResult::find($resultId);
            
            if (!$result) {
                abort(404, 'Match result not found');
            }

            $fieldBreakdown = $result->field_breakdown;
            
            if (!$fieldBreakdown || empty($fieldBreakdown)) {
                abort(404, 'No field breakdown data available');
            }

            // Generate CSV content
            $csv = $this->generateCSV($fieldBreakdown);
            
            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_His');
            $filename = "field-breakdown-{$resultId}-{$timestamp}.csv";

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            Log::error('Error exporting field breakdown', [
                'result_id' => $resultId,
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Unable to export field breakdown');
        }
    }

    /**
     * Generate CSV content from field breakdown data
     * 
     * @param array $fieldBreakdown
     * @return string
     */
    protected function generateCSV(array $fieldBreakdown): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Write CSV header
        fputcsv($output, [
            'Field Name',
            'Category',
            'Status',
            'Uploaded Value',
            'Existing Value',
            'Uploaded Normalized',
            'Existing Normalized',
            'Confidence Score'
        ]);

        // Process core fields
        if (isset($fieldBreakdown['core_fields'])) {
            foreach ($fieldBreakdown['core_fields'] as $fieldName => $fieldData) {
                fputcsv($output, [
                    $fieldName,
                    $fieldData['category'] ?? 'core',
                    $fieldData['status'] ?? '',
                    $fieldData['uploaded'] ?? '',
                    $fieldData['existing'] ?? '',
                    $fieldData['uploaded_normalized'] ?? '',
                    $fieldData['existing_normalized'] ?? '',
                    $fieldData['confidence'] ?? ''
                ]);
            }
        }

        // Process template fields
        if (isset($fieldBreakdown['template_fields'])) {
            foreach ($fieldBreakdown['template_fields'] as $fieldName => $fieldData) {
                fputcsv($output, [
                    $fieldName,
                    $fieldData['category'] ?? 'template',
                    $fieldData['status'] ?? '',
                    $fieldData['uploaded'] ?? '',
                    $fieldData['existing'] ?? '',
                    $fieldData['uploaded_normalized'] ?? '',
                    $fieldData['existing_normalized'] ?? '',
                    $fieldData['confidence'] ?? ''
                ]);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
