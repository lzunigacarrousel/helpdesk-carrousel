<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ProviderParticipationService
{
    public const WORK_STATUS_LABELS = [
        'ANALYSIS' => 'En análisis / diagnóstico',
        'WAITING_CARROUSEL' => 'Esperando información de Carrousel',
        'WAITING_THIRD_PARTY' => 'Esperando tercero / fabricante',
        'IN_PROGRESS' => 'En atención / trabajando',
        'VALIDATING' => 'En validación',
        'READY_FOR_REVIEW' => 'Listo para revisión de Carrousel',
    ];

    public function __construct(private ?object $pdo = null)
    {
    }

    private function pdo(): object
    {
        return $this->pdo ?? Database::pdo();
    }

    public function rows(?int $nowTs = null): array
    {
        return [];
    }

    public static function buildCycles(
        array $users,
        array $events,
        array $comments,
        array $attachments,
        array $reports,
        ?int $nowTs = null
    ): array {
        return [];
    }
}
