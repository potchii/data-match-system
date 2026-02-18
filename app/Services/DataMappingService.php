<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Shared\Date;

class DataMappingService
{
    public function map(array $row)
        {
            $last = trim(strtoupper($row['surname'] ?? ''));
            $first = trim(strtoupper($row['firstname'] ?? ''));
            $middle = trim(strtoupper($row['middlename'] ?? ''));
            $ext = trim(strtoupper($row['extension'] ?? ''));

            return [
                'uid'               => $row['regsno'] ?? ($row['id'] ?? 'SYS-' . strtoupper(uniqid())),                
                'last_name'         => $last,
                'first_name'        => $first,
                'middle_name'       => $middle ?: null,
                'suffix'            => $ext ?: null,
                'birthday'          => $this->transformDate($row['dob'] ?? null),
                'gender'            => trim(strtoupper($row['sex'] ?? '')),
                'civil_status'      => trim(strtoupper($row['status'] ?? '')),
                'street_no'         => $row['streetno'] ?? null, 
                'street'            => isset($row['address']) ? trim(strtoupper($row['address'])) : null,
                'city'              => isset($row['brgydescription']) ? trim(strtoupper($row['brgydescription'])) : 'NOT SPECIFIED',
                'province'          => 'NOT SPECIFIED',
                'registration_date' => $this->transformDate($row['registrationdate'] ?? null),
                'full_name_meta'    => $row['full'] ?? trim("$last, $first $middle $ext"), 
            ];
        }

    private function transformDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Excel numeric date
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // String date
        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null; 
        }

        return date('Y-m-d', $timestamp);
    }
}