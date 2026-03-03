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

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uploaded_first_name', 'like', "%{$search}%")
                    ->orWhere('uploaded_middle_name', 'like', "%{$search}%")
                    ->orWhere('uploaded_last_name', 'like', "%{$search}%")
                    ->orWhere('uploaded_record_id', 'like', "%{$search}%");
            });
        }
        
        $results = $query->orderBy('id', 'asc')->paginate(20);
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
     * Get trends data for batch
     *
     * @param int $batchId
     * @return JsonResponse
     */
    /**
     * Get trends data for batch
     * 
     * @param int $batchId
     * @return JsonResponse
     */
    public function getBatchTrends(int $batchId): JsonResponse
    {
        try {
            $batch = UploadBatch::find($batchId);

            if (!$batch) {
                Log::warning('Trends requested for non-existent batch', ['batch_id' => $batchId]);
                return response()->json([
                    'error' => 'Batch not found',
                    'message' => 'The requested batch does not exist.'
                ], 404);
            }

            $trends = $this->matchAnalyticsService->calculateBatchTrends($batchId);
            $matchStatusChart = $this->matchAnalyticsService->generateMatchStatusChart($batchId);
            $templateFields = $this->matchAnalyticsService->getTemplateFieldsInfo($batchId);

            return response()->json([
                'batch_id' => $batchId,
                'trends' => $trends,
                'match_status_chart' => $matchStatusChart,
                'template_fields' => $templateFields,
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating batch trends', [
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
     * Export all duplicates with their matched base records
     * 
     * @param Request $request
     * @return Response
     */
    public function exportDuplicates(Request $request): Response
    {
        try {
            $query = MatchResult::query()->with([
                'batch',
                'matchedRecord.originBatch'
            ]);
            
            // Apply filters
            if ($request->filled('batch_id')) {
                $query->where('batch_id', $request->batch_id);
            }
            
            if ($request->filled('status')) {
                $query->where('match_status', $request->status);
            } else {
                // By default, only export MATCHED and POSSIBLE DUPLICATE (exclude NEW RECORD)
                $query->whereIn('match_status', ['MATCHED', 'POSSIBLE DUPLICATE']);
            }
            
            $results = $query->orderBy('batch_id', 'asc')
                            ->orderBy('id', 'asc')
                            ->get();
            
            // Generate CSV content
            $csv = $this->generateDuplicatesCSV($results);
            
            // Generate filename with timestamp and filters
            $timestamp = now()->format('Y-m-d_His');
            $batchSuffix = $request->filled('batch_id') ? "-batch{$request->batch_id}" : '-all-batches';
            $statusSuffix = $request->filled('status') ? '-' . strtolower(str_replace(' ', '-', $request->status)) : '-duplicates';
            $filename = "duplicates-report{$batchSuffix}{$statusSuffix}-{$timestamp}.csv";

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            Log::error('Error exporting duplicates', [
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Unable to export duplicates report');
        }
    }

    /**
     * Generate CSV content for duplicates report
     * 
     * @param \Illuminate\Support\Collection $results
     * @return string
     */
    protected function generateDuplicatesCSV($results): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Write CSV header
        fputcsv($output, [
            'Row ID',
            'Batch ID',
            'Uploaded First Name',
            'Uploaded Middle Name',
            'Uploaded Last Name',
            'Uploaded Record ID',
            'Match Status',
            'Confidence Score',
            'Matched Base First Name',
            'Matched Base Middle Name',
            'Matched Base Last Name',
            'Matched Base UID',
            'Matched Base Row ID',
            'Source Batch ID',
            'Source Batch File',
            'Matched Fields',
            'Total Fields',
            'Birthday Match',
            'Gender Match',
            'Address Match'
        ]);

        // Write data rows
        $rowId = 1;
        foreach ($results as $result) {
            $fieldBreakdown = $result->field_breakdown ?? [];
            
            // Extract match details from field breakdown
            $birthdayMatch = 'N/A';
            $genderMatch = 'N/A';
            $addressMatch = 'N/A';
            
            if (isset($fieldBreakdown['core_fields'])) {
                if (isset($fieldBreakdown['core_fields']['birthday'])) {
                    $birthdayMatch = $fieldBreakdown['core_fields']['birthday']['status'] === 'matched' ? 'Yes' : 'No';
                }
                if (isset($fieldBreakdown['core_fields']['gender'])) {
                    $genderMatch = $fieldBreakdown['core_fields']['gender']['status'] === 'matched' ? 'Yes' : 'No';
                }
                if (isset($fieldBreakdown['core_fields']['address'])) {
                    $addressMatch = $fieldBreakdown['core_fields']['address']['status'] === 'matched' ? 'Yes' : 'No';
                }
            }
            
            fputcsv($output, [
                $rowId++,
                $result->batch_id,
                $result->uploaded_first_name ?? '',
                $result->uploaded_middle_name ?? '',
                $result->uploaded_last_name ?? '',
                $result->uploaded_record_id ?? '',
                $result->match_status,
                number_format($result->confidence_score, 1),
                $result->matchedRecord->first_name ?? '',
                $result->matchedRecord->middle_name ?? '',
                $result->matchedRecord->last_name ?? '',
                $result->matchedRecord->uid ?? '',
                $result->matchedRecord->origin_match_result_id ?? $result->matchedRecord->id ?? '',
                $result->matchedRecord->origin_batch_id ?? '',
                $result->matchedRecord->originBatch->file_name ?? '',
                $fieldBreakdown['matched_fields'] ?? 0,
                $fieldBreakdown['total_fields'] ?? 0,
                $birthdayMatch,
                $genderMatch,
                $addressMatch
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
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
