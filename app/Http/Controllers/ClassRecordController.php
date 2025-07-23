<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ClassRecordImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Teacher;

class ClassRecordController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'class_record_file' => 'required|mimes:xlsx,csv'
        ]);

        // Create a new instance of our import class
        $import = new ClassRecordImport();

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
        $data = $request->json()->all();


        // $userId = Auth::user()->id;
        // $teacherId = Teacher::where('user_id', $userId)->value('id');

        $headerData = $data['headerData'];
        $maleStudents = $data['maleStudents'];
        $femaleStudents = $data['femaleStudents'];
        // Set gender for each male student
        foreach ($maleStudents as &$student) {
            $student['gender'] = 'male';
        }
        unset($student);

        foreach ($femaleStudents as &$student) {
            $student['gender'] = 'female';
        }
        unset($student);
        // Combine male and female students for processing
        $allStudents = array_merge($maleStudents, $femaleStudents);

        foreach (array_merge($maleStudents, $femaleStudents) as $studentData) {
            Student::updateOrCreate(
                ['lrn' => $studentData['lrn']],
                [
                    'first_name' => $studentData['first_name'],
                    'last_name' => $studentData['last_name'],
                    'section_id' => 1,
                    'gender' => in_array($studentData, $maleStudents) ? 'male' : 'female',
                    'teacher_id' => 1,
                ]
            );
        }
        // foreach ($allStudents as $studentData) {
        //     $student = Student::firstOrNew(['lrn' => $studentData['lrn']]);
        //     $student->first_name = $studentData['first_name'];
        //     $student->last_name = $studentData['last_name'];
        //     $student->gender = $studentData['gender'];
        //     $student->teacher_id = $teacherId;
        //     dd($student);
        //     $student->save();
        // }

        return response()->json(['success' => true, 'message' => 'Class record saved successfully!']);
    }
}
