<?php

namespace App\Services;

use Symfony\Component\Process\ExecutableFinder;

class SnappyBinaryResolver
{
    private const WINDOWS_PDF = '\\vendor\\wemersonjanuario\\wkhtmltopdf-windows\\bin\\64bit\\wkhtmltopdf.exe';
    private const WINDOWS_IMAGE = '\\vendor\\wemersonjanuario\\wkhtmltopdf-windows\\bin\\64bit\\wkhtmltoimage.exe';
    private const VENDOR_LINUX_PDF = 'vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64';
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
        if ($this->isValidOverride($this->pdfBinaryOverride)) {
            return $this->pdfBinaryOverride;
        }

        if (\PHP_OS_FAMILY === 'Windows') {
            return $this->buildWindowsPath(self::WINDOWS_PDF);
        }

        $vendorBinary = $this->buildVendorBinary(self::VENDOR_LINUX_PDF);
        if ($vendorBinary) {
            return $vendorBinary;
        }

        return $this->resolveUnixBinary(self::LINUX_PDF_DEFAULTS, 'wkhtmltopdf');
    }

    public function getImageBinary(): string
    {
        if ($this->isValidOverride($this->imageBinaryOverride)) {
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

    private function buildVendorBinary(string $relativePath): ?string
    {
        $path = $this->projectDir . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);

        return $this->isUsableBinary($path) ? $path : null;
    }

    private function isUsableBinary(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && file_exists($path)
            && @is_executable($path);
    }

    private function isValidOverride(?string $override): bool
    {
        if (!is_string($override) || $override === '') {
            return false;
        }

        // Allow pointing to a binary available in PATH (e.g. "wkhtmltopdf").
        if (str_contains($override, DIRECTORY_SEPARATOR) || str_contains($override, '/')) {
            return $this->isUsableBinary($override);
        }

        $detected = $this->executableFinder->find($override);

        return $this->isUsableBinary($detected);
    }
}
