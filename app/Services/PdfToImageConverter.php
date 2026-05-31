<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PdfToImageConverter
{
    public function convertPdfFile(string $absolutePdfPath, string $publicPreviewRelativePath): bool
    {
        if (! is_file($absolutePdfPath)) {
            Log::warning('PdfToImageConverter: PDF no encontrado.', ['path' => $absolutePdfPath]);

            return false;
        }

        $outputAbsolute = Storage::disk('public')->path($publicPreviewRelativePath);
        $outputDir = dirname($outputAbsolute);

        if (! is_dir($outputDir) && ! mkdir($outputDir, 0755, true) && ! is_dir($outputDir)) {
            return false;
        }

        $nodeBinary = $this->resolveNodeBinary();

        if ($nodeBinary === null) {
            Log::warning('PdfToImageConverter: Node.js no encontrado. Instala Node o define NODE_BINARY en .env');

            return false;
        }

        $scriptPath = base_path('tools/pdf-to-image/convert.mjs');

        if (! is_file($scriptPath)) {
            Log::warning('PdfToImageConverter: no se encontró convert.mjs');

            return false;
        }

        $process = new Process([
            $nodeBinary,
            $scriptPath,
            $absolutePdfPath,
            $outputAbsolute,
        ], base_path('tools/pdf-to-image'));

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($outputAbsolute) || filesize($outputAbsolute) === 0) {
            Log::warning('PdfToImageConverter: falló la conversión PDF a imagen.', [
                'node'  => $nodeBinary,
                'error' => trim($process->getErrorOutput().' '.$process->getOutput()),
            ]);

            @unlink($outputAbsolute);

            return false;
        }

        return true;
    }

    public function makePreviewRelativePath(): string
    {
        return 'archivos-convocatorias/previews/'.Str::uuid().'.png';
    }

    private function resolveNodeBinary(): ?string
    {
        $configured = env('NODE_BINARY');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\\Program Files\\nodejs\\node.exe',
            'C:\\Program Files (x86)\\nodejs\\node.exe',
            'node',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== 'node' && is_file($candidate)) {
                return $candidate;
            }
        }

        $process = Process::fromShellCommandline('where node');
        $process->run();

        if ($process->isSuccessful()) {
            $line = trim(strtok($process->getOutput(), "\r\n"));

            if ($line !== '' && is_file($line)) {
                return $line;
            }
        }

        return is_file('C:\\Program Files\\nodejs\\node.exe')
            ? 'C:\\Program Files\\nodejs\\node.exe'
            : null;
    }
}
