<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use PDO;
use RuntimeException;

final class TicketAttachmentService
{
    private const MAX_FILE_SIZE = 10485760;

    private const ALLOWED_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];

    private const OOXML_CONTAINER_MIME = [
        'application/zip',
        'application/x-zip-compressed',
        'application/x-compressed',
        'application/octet-stream',
        'application/vnd.ms-office',
    ];

    private const OOXML_MARKERS = [
        'xlsx' => 'xl/workbook.xml',
        'docx' => 'word/document.xml',
    ];

    public function storeUploadedFile(
        PDO $pdo,
        int $ticketId,
        ?int $commentId,
        ?int $activityId,
        string $visibility,
        array $file
    ): int {
        if ($ticketId <= 0) {
            throw new RuntimeException('No encontramos el caso para adjuntar el archivo.');
        }

        $visibility = strtoupper(trim($visibility));
        if (!in_array($visibility, ['PUBLIC', 'INTERNAL', 'EXTERNAL'], true)) {
            throw new RuntimeException('La visibilidad del archivo no es válida.');
        }

        if ($activityId !== null) {
            if ($activityId <= 0) {
                throw new RuntimeException('La evidencia no corresponde a esta actividad.');
            }
            $activity = $pdo->prepare(
                'SELECT COUNT(*) FROM ticket_activities WHERE id=? AND ticket_id=?'
            );
            $activity->execute([$activityId, $ticketId]);
            if ((int)$activity->fetchColumn() !== 1) {
                throw new RuntimeException('La evidencia no corresponde a esta actividad.');
            }
        }

        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No pudimos recibir el archivo. Intenta nuevamente.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('El archivo debe pesar máximo 10 MB.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('No pudimos validar el archivo.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower(trim((string)$finfo->file($tmp)));
        $original = trim((string)($file['name'] ?? 'archivo'));
        $ext = $this->resolveAllowedExtension($mime, $tmp, $original);
        if ($ext === null) {
            throw new RuntimeException('Ese tipo de archivo no está permitido. Formatos admitidos: PDF, imágenes, TXT, CSV, Word y Excel.');
        }
        $dir = 'storage/ticket_uploads/'.date('Y').'/'.date('m');
        $absolute = APP_ROOT.'/'.$dir;
        if (!is_dir($absolute) && !mkdir($absolute, 0775, true) && !is_dir($absolute)) {
            throw new RuntimeException('No pudimos preparar el almacenamiento del archivo.');
        }

        $stored = bin2hex(random_bytes(18)).'.'.$ext;
        $target = $absolute.'/'.$stored;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('No pudimos guardar el archivo.');
        }

        $sha = hash_file('sha256', $target) ?: null;
        if ($original === '') {
            $original = 'archivo.'.$ext;
        }

        try {
            $q = $pdo->prepare(
                'INSERT INTO ticket_attachments(
                    ticket_id,comment_id,activity_id,uploaded_by_user_id,visibility,
                    original_name,stored_name,storage_path,mime_type,size_bytes,sha256,created_at
                 ) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())'
            );
            $q->execute([
                $ticketId,
                $commentId,
                $activityId,
                Auth::id(),
                $visibility,
                $original,
                $stored,
                $dir.'/'.$stored,
                $mime,
                $size,
                $sha,
            ]);
        } catch (\Throwable $e) {
            @unlink($target);
            throw $e;
        }

        return (int)$pdo->lastInsertId();
    }

    private function resolveAllowedExtension(string $mime, string $tmp, string $originalName): ?string
    {
        if (isset(self::ALLOWED_MIME[$mime])) {
            return self::ALLOWED_MIME[$mime];
        }

        if (!in_array($mime, self::OOXML_CONTAINER_MIME, true)) {
            return null;
        }

        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset(self::OOXML_MARKERS[$extension])) {
            return null;
        }

        return $this->isValidOoxmlPackage($tmp, self::OOXML_MARKERS[$extension])
            ? $extension
            : null;
    }

    private function isValidOoxmlPackage(string $path, string $requiredMarker): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $signature = (string)fread($handle, 4);
        fclose($handle);
        if (!str_starts_with($signature, 'PK')) {
            return false;
        }

        // Los nombres de entradas ZIP aparecen en claro en el directorio del paquete.
        // El archivo ya pasó el límite de 10 MB, así que esta inspección queda acotada.
        $contents = @file_get_contents($path);
        if (!is_string($contents) || $contents === '') {
            return false;
        }

        return str_contains($contents, '[Content_Types].xml')
            && str_contains($contents, $requiredMarker);
    }
}
