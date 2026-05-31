<?php

namespace App\Support;

use Illuminate\Support\Str;

class StorageUrl
{
    public static function baseUrl(): string
    {
        if (! app()->runningInConsole() && request()->hasHeader('Host')) {
            return rtrim(request()->getSchemeAndHttpHost().request()->getBaseUrl(), '/');
        }

        return rtrim(config('app.url'), '/');
    }

    public static function publicUrl(string $storagePath): string
    {
        return self::baseUrl().'/storage/'.ltrim($storagePath, '/');
    }

    public static function apiUrl(string $path): string
    {
        return self::baseUrl().'/api/'.ltrim($path, '/');
    }

    public static function relativePath(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_contains($value, '/storage/')) {
            return ltrim(Str::after($value, '/storage/'), '/');
        }

        return ltrim($value, '/');
    }

    public static function normalize(?string $value): ?string
    {
        $path = self::relativePath($value);

        return $path !== null ? self::publicUrl($path) : null;
    }

    public static function tipoArchivo(?string $value): ?string
    {
        $path = self::relativePath($value);

        if ($path === null) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension === 'pdf' ? 'pdf' : 'imagen';
    }

    public static function mimeTypeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf'        => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            'webp'       => 'image/webp',
            default      => 'application/octet-stream',
        };
    }
}
