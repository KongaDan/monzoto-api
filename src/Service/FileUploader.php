<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    
    private string $publicDir;
    private string $privateDir;

    public function __construct(
        string $uploadPublicDir,
        string $uploadPrivateDir
    ) {
        $this->publicDir  = rtrim($uploadPublicDir, '/');
        $this->privateDir = rtrim($uploadPrivateDir, '/');
    }

    public function uploadPublic(UploadedFile $file, string $subDir): string
    {
        $filename = $this->generateFilename($file);

        $targetDir = $this->publicDir.'/'.$subDir;
        $this->ensureDirectoryExists($targetDir);

        $file->move(
            $this->publicDir.'/'.$subDir,
            $filename
        );

        return $filename;
    }

    public function uploadPrivate(UploadedFile $file, string $subDir): string
    {
        $filename = $this->generateFilename($file);

        $targetDir = $this->privateDir.'/'.$subDir;

        $this->ensureDirectoryExists($targetDir);

        $file->move(
            $this->privateDir.'/'.$subDir,
            $filename
        );

        return $filename;
    }

    public function delete(string $absolutePath): void
    {
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private function generateFilename(UploadedFile $file): string
    {
        return bin2hex(random_bytes(16)) . '.' . ($file->guessExtension() ?? 'bin');
    }

    private function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException('Impossible de créer le dossier : '.$dir);
            }
        }
    }

}
