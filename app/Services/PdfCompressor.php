<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PdfCompressor
{
    /**
     * Comprime un PDF y devuelve la ruta absoluta del archivo resultante.
     * Si no hay compresor disponible o falla, devuelve null.
     */
    public function compress(string $sourcePath, string $quality = 'ebook'): ?string
    {
        $binary = $this->resolveBinary();

        if ($binary === null) {
            Log::warning('PdfCompressor: Ghostscript no encontrado; se guardará el PDF sin comprimir.');

            return null;
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            return null;
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'pdf_cmp_');

        if ($outputPath === false) {
            return null;
        }

        @unlink($outputPath);
        $outputPath .= '.pdf';

        $settings = match (strtolower($quality)) {
            'screen'   => '/screen',
            'printer'  => '/printer',
            'prepress' => '/prepress',
            default    => '/ebook',
        };

        $process = new Process([
            $binary,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS='.$settings,
            '-dDetectDuplicateImages=true',
            '-dCompressFonts=true',
            '-dSubsetFonts=true',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-sOutputFile='.$outputPath,
            $sourcePath,
        ]);

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($outputPath) || filesize($outputPath) === 0) {
            Log::warning('PdfCompressor: falló la compresión.', [
                'error' => $process->getErrorOutput(),
            ]);
            @unlink($outputPath);

            return null;
        }

        $originalSize = filesize($sourcePath) ?: 0;
        $compressedSize = filesize($outputPath) ?: 0;

        if ($compressedSize >= $originalSize) {
            @unlink($outputPath);

            return null;
        }

        return $outputPath;
    }

    public function isAvailable(): bool
    {
        return $this->resolveBinary() !== null;
    }

    private function resolveBinary(): ?string
    {
        static $resolved = null;
        static $searched = false;

        if ($searched) {
            return $resolved;
        }

        $searched = true;
        $candidates = [];

        $configured = env('PDF_GS_BINARY');

        if (is_string($configured) && $configured !== '') {
            $candidates[] = $configured;
        }

        $candidates = array_merge($candidates, [
            'gswin64c',
            'gswin32c',
            'gs',
            'C:\\Program Files\\gs\\gs10.05.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.05.0\\bin\\gswin32c.exe',
        ]);

        foreach ($candidates as $candidate) {
            if ($this->isExecutableBinary($candidate)) {
                $resolved = $candidate;

                return $resolved;
            }
        }

        $resolved = $this->findGhostscriptInProgramFiles();

        return $resolved;
    }

    private function findGhostscriptInProgramFiles(): ?string
    {
        $roots = [
            'C:\\Program Files\\gs',
            'C:\\Program Files (x86)\\gs',
        ];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $versions = glob($root.DIRECTORY_SEPARATOR.'gs*', GLOB_ONLYDIR) ?: [];

            rsort($versions);

            foreach ($versions as $versionDir) {
                foreach (['gswin64c.exe', 'gswin32c.exe'] as $name) {
                    $path = $versionDir.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.$name;

                    if ($this->isExecutableBinary($path)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }

    private function isExecutableBinary(string $path): bool
    {
        if (is_file($path)) {
            if (stripos(PHP_OS_FAMILY, 'Windows') === 0 && str_ends_with(strtolower($path), '.exe')) {
                return true;
            }

            if (is_executable($path)) {
                return true;
            }
        }

        $finder = stripos(PHP_OS_FAMILY, 'Windows') === 0 ? 'where' : 'which';
        $process = Process::fromShellCommandline($finder.' '.escapeshellarg($path));
        $process->run();

        return $process->isSuccessful();
    }
}
