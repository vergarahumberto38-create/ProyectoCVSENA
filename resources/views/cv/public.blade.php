<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $profile->full_name }} - CV</title>
    @vite('resources/css/app.css')
</head>
<body class="sena-guest-shell min-h-screen py-10">
    <div class="max-w-3xl mx-auto">
        <div class="sena-card p-8">
            @include('cv.templates.ats-classic', ['profile' => $profile])
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">
            CV generado con {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
