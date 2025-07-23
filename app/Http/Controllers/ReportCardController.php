<?php

namespace App\Http\Controllers;

use App\Imports\ReportCardImport;
use App\Models\ReportCard;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportCardController extends Controller
{
    /**
     * Show the upload form.
     */

    /**
     * Handle the file upload and display the extracted data.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('file');

            $import = new ReportCardImport();

            Excel::import($import, $file);

            $extractedData = $import->getExtractedData();

            if (empty($extractedData)) {
                return redirect()->route('report-cards.index')->with('error', 'No data could be extracted from the uploaded file.');
            }

            return view('report-cards.show', ['reportCard' => $extractedData]);
        } catch (\Exception $e) {
            return redirect()->route('report-cards.index')->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }
}
