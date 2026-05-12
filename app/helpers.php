<?php

if (! function_exists('versioned_asset')) {
    function versioned_asset(string $path): string
    {
        static $manifest = null;

        if ($manifest === null) {
            $manifestPath = public_path('build/manifest.json');
            $manifest = is_file($manifestPath)
                ? json_decode((string) file_get_contents($manifestPath), true)
                : [];
        }

        if (is_array($manifest) && isset($manifest[$path])) {
            return asset(ltrim($manifest[$path], '/'));
        }

        return asset($path);
    }
}
