<?php

namespace App\Imports;

use App\Models\MainSystem;
use App\Models\MatchResult;
use App\Services\DataMappingService;
use App\Services\DataMatchService;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordImport implements ToCollection, WithHeadingRow
{
    private $batchId;

    public function __construct($batchId) {
        $this->batchId = $batchId;
    }

    public function collection(Collection $rows)
    {
        $mapping = new DataMappingService();
        $matcher = new DataMatchService();

        if ($rows->isEmpty()) {
            throw new \Exception('Uploaded file is empty.');
        }

        // Validate headers sa excel
        $requiredColumns = ['surname', 'firstname', 'dob'];
        $firstRow = $rows->first()->toArray();

        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $firstRow)) {
                throw new \Exception("Missing required column: {$column}. Please check your Excel headers.");
            }
        }
    
        DB::transaction(function () use ($rows, $mapping, $matcher) {
            foreach ($rows as $row) {
                $data = $mapping->map($row->toArray());
                
                // Generate UID BEFORE matching so wehave reference 
                if (empty($data['uid'])) {
                    $data['uid'] = 'SYS-' . strtoupper(uniqid());
                }

                $match = $matcher->checkMatch($data);
                $systemId = null;

                if ($match['status'] === 'NEW RECORD') {
                    $newRecord = MainSystem::create($data);
                    $systemId = $newRecord->id;
                } else {
                    // Match found (100%, 90%, 80%, etc.)
                    $systemId = $match['record']->id;
                }

                // Save result for Ernest's frontend
                MatchResult::create([
                    'batch_id'           => $this->batchId,
                    'uploaded_record_id' => $data['uid'],
                    'matched_system_id'  => $systemId,
                    'match_status'       => $match['status'],
                    'confidence_score'   => $match['score'],
                ]);
            }
        });
    }
}