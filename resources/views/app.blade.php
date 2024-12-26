<html>
<head>
    <title>Margatron - Editor</title>

    <?php if(config('app.force_https')): ?>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <?php endif; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    @vite('resources/js/app.ts')
    @routes
    @inertiaHead
</head>
<body>
@inertia
</body>
</html>
