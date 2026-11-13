<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $filename }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, sans-serif; color: #374151; background: #f9fafb; }
        main { text-align: center; padding: 24px; }
        a { display: inline-block; margin-top: 16px; padding: 10px 18px; border-radius: 6px; background: #f97316; color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <p><strong>{{ $filename }}</strong></p>
        <p>This file type can't be previewed in the browser.</p>
        <a href="{{ $downloadUrl }}" download>Download file</a>
    </main>
</body>
</html>
