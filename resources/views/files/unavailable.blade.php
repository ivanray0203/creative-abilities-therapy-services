<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>File unavailable</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, sans-serif; color: #374151; background: #f9fafb; }
        main { text-align: center; padding: 24px; max-width: 480px; }
    </style>
</head>
<body>
    <main>
        <p><strong>This file can't be loaded right now.</strong></p>
        @if ($isAdmin)
            <p>The connection to Google Drive has stopped working. Reconnect Google Drive from the admin dashboard, then try again.</p>
        @else
            <p>Please try again later, or contact the clinic if this keeps happening.</p>
        @endif
    </main>
</body>
</html>
