@extends('teacher.layout')

@section('title', 'Student QR Code')

@section('content')
    <main class="p-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Student QR Code</h6>
                    </div>
                    <div class="card-body text-center">
                        <h5>Student ID: STU1001</h5>
                        <h6>Student Name: Sample Student 1</h6>
                        <div class="my-4" id="qrcode"></div>
                        <p class="text-muted">Show this QR code to your teacher to mark your attendance</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate QR Code
            const qrcode = new QRCode(document.getElementById("qrcode"), {
                text: "STU1001",
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });
    </script>
@endpush
