<!DOCTYPE html>
<html>

<head>
    <title>Send Email via Resend</title>
    <style>
        .success {
            color: green;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .validation-error {
            color: red;
            font-size: 0.8em;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <h2>Send Email</h2>
    @if (session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('resend.email') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="to">To:</label>
            <input type="email" name="to" value="{{ old('to') }}" required style="width: 100%; padding: 8px;">
            @error('to')
                <div class="validation-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="subject">Subject:</label>
            <input type="text" name="subject" value="{{ old('subject') }}" required
                style="width: 100%; padding: 8px;">
            @error('subject')
                <div class="validation-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="message">Message:</label>
            <textarea name="message" rows="8" required style="width: 100%; padding: 8px;">{{ old('message') }}</textarea>
            @error('message')
                <div class="validation-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit"
            style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
            Send Email
        </button>
    </form>
</body>

</html>
