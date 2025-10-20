<?php

namespace App\Services;

use Symfony\Component\Process\ExecutableFinder;

class SnappyBinaryResolver
{
    private const WINDOWS_PDF = '\\vendor\\wemersonjanuario\\wkhtmltopdf-windows\\bin\\64bit\\wkhtmltopdf.exe';
    private const WINDOWS_IMAGE = '\\vendor\\wemersonjanuario\\wkhtmltopdf-windows\\bin\\64bit\\wkhtmltoimage.exe';
    private const LINUX_PDF_DEFAULTS = [
        '/usr/bin/wkhtmltopdf',
        '/usr/local/bin/wkhtmltopdf',
        '/snap/bin/wkhtmltopdf',
    ];
    private const LINUX_IMAGE_DEFAULTS = [
        '/usr/bin/wkhtmltoimage',
        '/usr/local/bin/wkhtmltoimage',
        '/snap/bin/wkhtmltoimage',
    ];

    public function __construct(
        private readonly string $projectDir,
        private readonly ExecutableFinder $executableFinder,
        private readonly ?string $pdfBinaryOverride = null,
        private readonly ?string $imageBinaryOverride = null,
    ) {
    }

    public function getPdfBinary(): string
    {
        if ($this->pdfBinaryOverride) {
            return $this->pdfBinaryOverride;
        }

        if (\PHP_OS_FAMILY === 'Windows') {
            return $this->buildWindowsPath(self::WINDOWS_PDF);
        }

        return $this->resolveUnixBinary(self::LINUX_PDF_DEFAULTS, 'wkhtmltopdf');
    }

    public function getImageBinary(): string
    {
        if ($this->imageBinaryOverride) {
            return $this->imageBinaryOverride;
        }

        if (\PHP_OS_FAMILY === 'Windows') {
            return $this->buildWindowsPath(self::WINDOWS_IMAGE);
        }

        return $this->resolveUnixBinary(self::LINUX_IMAGE_DEFAULTS, 'wkhtmltoimage');
    }

    private function buildWindowsPath(string $suffix): string
    {
        return rtrim($this->projectDir, '\\/') . $suffix;
    }

    private function resolveUnixBinary(array $candidates, string $binaryName): string
    {
        $detected = $this->executableFinder->find($binaryName);
        if ($this->isUsableBinary($detected)) {
            return $detected;
        }

        foreach ($candidates as $candidate) {
            if ($this->isUsableBinary($candidate)) {
                return $candidate;
            }
        }

        return $binaryName;
    }

    private function isUsableBinary(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && file_exists($path)
            && @is_executable($path);
    }
}
