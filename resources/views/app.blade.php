<html>
<head>
    <title>Margatron - Editor</title>

    <?php if(config('app.force_https')): ?>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <?php endif; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- Apply theme settings from localStorage before Vue loads to prevent flicker -->
    <script>
        // Check if dark theme is enabled in localStorage
        const isDarkTheme = localStorage.getItem('is_theme_dark') === '1';

        // Apply dark theme class if needed
        if (isDarkTheme) {
            document.documentElement.classList.add('app-dark');
        }

        // Try to get Pinia store data
        try {
            const layoutStore = JSON.parse(localStorage.getItem('layout'));
            if (layoutStore && layoutStore.darkTheme) {
                document.documentElement.classList.add('app-dark');
            }
        } catch (e) {
            // Ignore errors, fallback to the is_theme_dark check above
        }
    </script>

    @vite('resources/js/app.ts')
    @routes
    @inertiaHead
</head>
<body>
@inertia
</body>
</html>
