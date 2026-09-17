<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $profile->full_name }} - CV</title>
</head>
<body>
    @include('cv.templates.ats-classic', ['profile' => $profile, 'photoSrc' => $photoSrc])
</body>
</html>
