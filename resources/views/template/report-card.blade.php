<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner's Progress Report Card</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background-color: #ccc;
        }

        .report-card-container {
            width: 13in;
            min-height: 8.5in;
            margin: 1rem auto;
            background-color: white;
            padding: 0.5in;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 9pt;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 1.5px 4px;
            /* Fine-tuned padding */
            vertical-align: middle;
            height: 19px;
            /* Consistent row height */
        }

        .no-border {
            border: none;
        }

        .border-bottom {
            border-bottom: 1px solid #000;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-xs {
            font-size: 8.5pt;
        }

        /* Adjusted for accuracy */
        .text-sm {
            font-size: 10pt;
        }

        @media print {
            @page {
                size: legal landscape;
            }

            body {
                background-color: white;
            }

            .report-card-container {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0.4in;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    <div class="report-card-container">


        <!-- Section 2: Main Body (Two-Column Layout) -->
        <div class="flex flex-grow space-x-6 mt-4">

            <!-- LEFT COLUMN -->
            <div class="w-1/2 flex flex-col">
                <!-- Student Info Table -->
                <!-- Section 1: Header -->
                <div class="text-center">
                    <p class="text-sm">Republic of the Philippines</p>
                    <p class="font-bold text-sm">Department of Education</p>
                    <p class="text-sm">REGION XI</p>
                    <p class="text-sm"> PANABO CITY</p>
                    <p class="font-bold text-sm">STA. CRUZ ELEM. SCHOOL</p>
                </div>

                <table class="text-xs">
                    <tr>
                        <td>Name:</td>
                        <td class="font-bold" colspan="3" id="studentName">{{ $student['name'] }}</td>
                    </tr>
                    <tr>
                        <td>Age:</td>
                        <td class="font-bold"></td>
                        <td class="pl-4">Sex:</td>
                        <td class="font-bold">{{ $student['gender'] }}</td>
                    </tr>
                    <tr>
                        <td>Grade:</td>
                        <td class="font-bold">">{{ $student['grade'] }}</td>
                        <td class="pl-4">Section:</td>
                        <td class="font-bold">DAGOHOY</td>
                    </tr>
                    <tr>
                        <td>LRN:</td>
                        <td class="font-bold">466060150011
                        </td>
                        <td class="pl-4">School Year:</td>
                        <td class="font-bold">2025-2026</td>
                    </tr>
                </table>

                <div class="mt-2 text-xs">
                    <p class="font-bold">Dear Parents:</p>
                    <p>This report card shows the ability and progress your child has made in the different learning
                        areas as well as his/her core values. The school welcomes you should you desire to know more
                        about your child's progress.</p>
                </div>

                <div class="flex justify-between mt-2 text-xs">
                    <div class="w-1/2 text-center pt-4">
                        <p class="border-bottom font-bold"></p>
                        <p>Head Teacher III</p>
                    </div>
                    <div class="w-1/2 text-center pt-4">
                        <p class="border-bottom font-bold">Christian S. Plasabas</p>
                        <p>Adviser</p>
                    </div>
                </div>

                <div class="mt-4 text-xs">
                    <p class="font-bold text-center">REPORT ON LEARNING PROGRESS AND ACHIEVEMENT</p>
                    <table id="gradesTable">
                        <thead>
                            <tr>
                                <th rowspan="2" class="w-2/5">Learning Areas</th>
                                <th colspan="4">Quarter</th>
                                <th rowspan="2">Final Grade</th>
                                <th rowspan="2">Remarks</th>
                            </tr>
                            <tr>
                                <th>1</th>
                                <th>2</th>
                                <th>3</th>
                                <th>4</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="subject">Mathematics</td>
                                <td class="text-center grade">79</td>
                                <td class="text-center">80</td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="font-bold subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="pl-4 subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="pl-4 subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="pl-4 subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="pl-4 subject"></td>
                                <td class="text-center grade"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td class="font-bold">General Average</td>
                                <td colspan="4"></td>
                                <td class="text-center"></td>
                                <td class="text-center" id="ai-remarks-cell"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <table class="mt-2 text-xs">
                    <thead>
                        <tr>
                            <th>Descriptors</th>
                            <th>Grading Scale</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Outstanding</td>
                            <td>90-100</td>
                            <td>Passed</td>
                        </tr>
                        <tr>
                            <td>Very Satisfactory</td>
                            <td>85-89</td>
                            <td>Passed</td>
                        </tr>
                        <tr>
                            <td>Satisfactory</td>
                            <td>80-84</td>
                            <td>Passed</td>
                        </tr>
                        <tr>
                            <td>Fair Satisfactory</td>
                            <td>75-79</td>
                            <td>Passed</td>
                        </tr>
                        <tr>
                            <td>Did not meet Expectation</td>
                            <td>Below 75</td>
                            <td>Failed</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-auto pt-4 text-xs">
                    <p class="font-bold text-center">Certificate of Transfer</p>
                    <table class="mt-1">
                        <tbody>
                            <tr>
                                <td class="w-[15%]">Admitted in Grade:</td>
                                <td class="w-[35%]"></td>
                                <td class="w-[10%] pl-4">Section:</td>
                                <td class="w-[40%]"></td>
                            </tr>
                            <tr>
                                <td class="pt-2">Eligibility for Admission to Grade:</td>
                                <td class="pt-2" colspan="3"></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-1">Approved:</p>
                    <div class="flex justify-between mt-2">
                        <div class="w-1/2 text-center pt-2">
                            <p class="border-bottom font-bold"></p>
                            <p>HEAD TEACHER III</p>
                        </div>
                        <div class="w-1/2 text-center pt-2">
                            <p class="border-bottom font-bold">Christian S. Plasabas</p>
                            <p>Teacher</p>
                        </div>
                    </div>
                    <p class="mt-2 font-bold">Cancellation of Eligibility to Transfer</p>
                    <table class="mt-1">
                        <tbody>
                            <tr>
                                <td class="w-[15%]">Admitted in:</td>
                                <td class="w-[35%]"></td>
                                <td class="w-[10%] pl-4">Date:</td>
                                <td class="w-[40%]"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex justify-end mt-2">
                        <div class="w-1/2 text-center pt-2">
                            <p class="border-bottom font-bold"></p>
                            <p>HEAD TEACHER III</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="w-1/2 flex flex-col">
                <div class="text-xs">
                    <p class="font-bold text-center">REPORT ON ATTENDANCE</p>
                    <table>
                        <thead>
                            <tr>
                                <th class="font-normal">Month</th>
                                <th>Jun</th>
                                <th>Jul</th>
                                <th>Aug</th>
                                <th>Sep</th>
                                <th>Oct</th>
                                <th>Nov</th>
                                <th>Dec</th>
                                <th>Jan</th>
                                <th>Feb</th>
                                <th>Mar</th>
                                <th>Apr</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-xs">No. of School Days</td>
                                <td>30</td>
                                <td>31</td>
                                <td>31</td>
                                <td>30</td>
                                <td>31</td>
                                <td>30</td>
                                <td>31</td>
                                <td>28</td>
                                <td>31</td>
                                <td>31</td>
                                <td>30</td>
                                <td>334</td>
                            </tr>
                            <tr>
                                <td class="text-xs">No. of Days Present</td>
                                <td>0</td>
                                <td>1</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td class="text-xs">No. of Days Absent</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-xs">
                    <p class="font-bold text-center">REPORTS ON LEARNERS OBSERVED VALUES</p>
                    <table>
                        <thead>
                            <tr>
                                <th rowspan="2">Core Values</th>
                                <th rowspan="2" class="w-1/2">Behavior Statement</th>
                                <th colspan="4">Quarter</th>
                            </tr>
                            <tr>
                                <th>1</th>
                                <th>2</th>
                                <th>3</th>
                                <th>4</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td rowspan="2">1. Maka-Diyos</td>
                                <td class="text-xs">Expresses one's spiritual beliefs while respecting the spiritual
                                    beliefs of others.</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="text-xs">Shows adherence to ethical principles by upholding truth.</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td rowspan="2">2. Makatao</td>
                                <td class="text-xs">Is sensitive to individual, social, and cultural differences</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="text-xs">Demonstrates contributions towards solidarity.</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>3. Maka-Kalikasan</td>
                                <td class="text-xs">Cares for the environment and utilizes resources wisely,
                                    judiciously and economically.</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>4. Maka-Bansa</td>
                                <td class="text-xs">Demonstrates pride in being a Filipino; exercises the rights and
                                    responsibilities of a Filipino citizen.</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between mt-2 text-xs">
                    <div class="w-1/2">
                        <p class="font-bold">Marking</p>
                        <p>AO</p>
                        <p>SO</p>
                        <p>RO</p>
                        <p>NO</p>
                    </div>
                    <div class="w-1/2">
                        <p class="font-bold">Non-numerical Rating</p>
                        <p>Always Observed</p>
                        <p>Sometimes Observed</p>
                        <p>Rarely Observed</p>
                        <p>Not Observed</p>
                    </div>
                </div>

                <div class="mt-4 text-xs">
                    <p class="font-bold text-center">PARENT/GUARDIAN'S SIGNATURE</p>
                    <p class="mt-2">1st Quarter <span class="border-bottom inline-block w-3/5 float-right"></span>
                    </p>
                    <p class="mt-2">2nd Quarter <span class="border-bottom inline-block w-3/5 float-right"></span>
                    </p>
                    <p class="mt-2">3rd Quarter <span class="border-bottom inline-block w-3/5 float-right"></span>
                    </p>
                    <p class="mt-2">4th Quarter <span class="border-bottom inline-block w-3/5 float-right"></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Section 3: Footer -->

    </div>
    <script>
        // Wait for the entire page to load before triggering the print dialog
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>
