<?php

namespace App\Http\Controllers;

use App\Imports\ClassRecordImport;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ClassRecordController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'class_record_file' => 'required|mimes:xlsx,csv',
        ]);

        // Create a new instance of our import class
        $import = new ClassRecordImport;

        // Import the file and pass our import object to it
        Excel::import($import, $request->file('class_record_file'));

        // The import object now holds our extracted data
        $headerData = $import->getExtractedHeaderData();
        $maleStudents = $import->getMaleStudents();
        $femaleStudents = $import->getFemaleStudents();

        // Return the consolidated data as a JSON response
        return response()->json([
            'headerData' => $headerData,
            'maleStudents' => $maleStudents,
            'femaleStudents' => $femaleStudents,
        ]);
    }

    public function saveClassRecord(Request $request)
    {
        try {
            $data = $request->json()->all();

            $userId = Auth::user()->id;
            $teacherId = Teacher::where('user_id', $userId)->value('id');

            $headerData = $data['headerData'];
            $maleStudents = $data['maleStudents'];
            $femaleStudents = $data['femaleStudents'];
            $maleStudents = array_map(function ($student) {
                $student['gender'] = 'male';

                return $student;
            }, $data['maleStudents']);

            $femaleStudents = array_map(function ($student) {
                $student['gender'] = 'female';

                return $student;
            }, $data['femaleStudents']);

            // Combine students into one list for a single loop
            $allStudents = array_merge($maleStudents, $femaleStudents);
            $existingFullNames = []; // Array to collect names of duplicates
            foreach ($allStudents as $studentData) {
                $student = Student::firstOrCreate(
                    [
                        'first_name' => $studentData['first_name'],
                        'last_name' => $studentData['last_name'],
                    ],
                    [
                        'student_id' => Student::generateStudentId(),
                        'lrn' => $studentData['lrn'] ?? null,
                        'first_name' => $studentData['first_name'],
                        'last_name' => $studentData['last_name'],
                        'section_id' => $data['section_id'] ?? null,
                        'gender' => in_array($studentData, $maleStudents) ? 'male' : 'female',
                        'teacher_id' => $teacherId,
                    ]
                );
                if ($student->wasRecentlyCreated) {
                    Log::info('ClassRecord import created new student', [
                        'student_id' => $student->id,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'lrn' => $student->lrn,
                        'section_id' => $data['section_id'] ?? null,
                    ]);
                } else {
                    // Student already existed. Instead of returning, collect their name.
                    $existingFullNames[] = $student->last_name.', '.$student->first_name;
                }
            }

            if (! empty($existingFullNames)) {
                // If the duplicates array is not empty, return a single conflict error.
                $count = count($existingFullNames);
                $plural = $count === 1 ? 'student' : 'students';
                $studentList = '';
                foreach ($existingFullNames as $index => $name) {
                    $studentList .= ($index + 1).'. '.$name."\n";
                }
                $studentList = trim($studentList);

                return response()->json([
                    'error' => true,
                    'message' => "Found {$count} duplicate {$plural}. The following students already exist and were not re-added:

{$studentList}",
                ], 409); // 409 Conflict
            }

            return response()->json(['success' => true, 'message' => 'Class record saved successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['error' => true, 'message' => 'Failed to save class record: '.$th->getMessage()]);
        }
    }
}
