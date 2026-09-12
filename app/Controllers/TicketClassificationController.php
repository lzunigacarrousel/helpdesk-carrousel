<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http};
use App\Services\{ScopeService,TicketClassificationService};
use PDO;

final class TicketClassificationController
{
    public function update(): void
    {
        Auth::requirePermission('tickets.classify');
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int)Http::post('ticket_id');
        $requestType = strtoupper(trim(Http::post('request_type')));
        $impact = strtoupper(trim(Http::post('impact')));
        $urgency = strtoupper(trim(Http::post('urgency')));
        $priorityOverride = strtoupper(trim(Http::post('priority_override')));
        $note = trim(Http::post('classification_note'));

        if ($id <= 0) {
            $this->back($id, 'No encontramos el caso que deseas clasificar.');
        }
        if (!TicketClassificationService::validRequestType($requestType)) {
            $this->back($id, 'Selecciona si se trata de un problema o de una solicitud.');
        }
        if (!TicketClassificationService::validImpact($impact)) {
            $this->back($id, 'Selecciona a cuántas personas o ubicaciones está afectando.');
        }
        if (!TicketClassificationService::validUrgency($urgency)) {
            $this->back($id, 'Selecciona qué tan urgente es la atención.');
        }
        if ($priorityOverride !== '' && !TicketClassificationService::validPriority($priorityOverride)) {
            $this->back($id, 'La prioridad seleccionada no es válida.');
        }

        $calculatedPriority = TicketClassificationService::calculatePriority($impact, $urgency);
        $priority = $priorityOverride !== '' ? $priorityOverride : $calculatedPriority;
        $prioritySource = ($priorityOverride !== '' && $priorityOverride !== $calculatedPriority) ? 'MANUAL' : 'CALCULATED';

        if ($prioritySource === 'MANUAL' && mb_strlen($note) < 5) {
            $this->back($id, 'Explica brevemente por qué la prioridad debe ser distinta a la sugerida.');
        }

        $pdo = Database::pdo();
        [$scopeSql, $scopeParams] = (new ScopeService())->ticketConstraint('t');
        $q = $pdo->prepare(
            "SELECT t.*
             FROM tickets t
             WHERE t.id=? AND t.deleted_at IS NULL AND ({$scopeSql})
             LIMIT 1"
        );
        $q->execute(array_merge([$id], $scopeParams));
        $ticket = $q->fetch();

        if (!$ticket) {
            $this->back($id, 'Ese caso no está disponible dentro de tu alcance.');
        }
        if (in_array((string)$ticket['status'], ['RESOLVED', 'CLOSED', 'CANCELLED'], true)) {
            $this->back($id, 'La clasificación ya no puede cambiarse porque el caso está finalizado.');
        }

        $before = [
            'request_type' => $ticket['request_type'] ?? null,
            'impact' => $ticket['impact'] ?? null,
            'urgency' => $ticket['urgency'] ?? null,
            'priority' => $ticket['priority'] ?? null,
            'priority_source' => $ticket['priority_source'] ?? 'LEGACY',
            'sla_policy_id' => $ticket['sla_policy_id'] ?? null,
            'first_response_due_at' => $ticket['first_response_due_at'] ?? null,
            'resolution_due_at' => $ticket['resolution_due_at'] ?? null,
        ];

        [$slaId, $firstDue, $resolutionDue] = $this->slaFor(
            $pdo,
            (int)($ticket['category_id'] ?? 0),
            $priority,
            (string)$ticket['created_at']
        );

        $after = [
            'request_type' => $requestType,
            'impact' => $impact,
            'urgency' => $urgency,
            'calculated_priority' => $calculatedPriority,
            'priority' => $priority,
            'priority_source' => $prioritySource,
            'classification_note' => $note !== '' ? $note : null,
            'sla_policy_id' => $slaId,
            'first_response_due_at' => $firstDue,
            'resolution_due_at' => $resolutionDue,
        ];

        $changed =
            (string)($before['request_type'] ?? '') !== $requestType ||
            (string)($before['impact'] ?? '') !== $impact ||
            (string)($before['urgency'] ?? '') !== $urgency ||
            (string)($before['priority'] ?? '') !== $priority ||
            (string)($before['priority_source'] ?? '') !== $prioritySource;

        if (!$changed) {
            Flash::set('La clasificación ya coincide con los valores seleccionados.', 'info');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);
            exit;
        }

        Database::transaction(function (PDO $pdo) use (
            $id,
            $requestType,
            $impact,
            $urgency,
            $priority,
            $prioritySource,
            $slaId,
            $firstDue,
            $resolutionDue,
            $before,
            $after
        ): void {
            $s = $pdo->prepare(
                "UPDATE tickets
                 SET request_type=?, impact=?, urgency=?, priority=?, priority_source=?,
                     sla_policy_id=?, first_response_due_at=?, resolution_due_at=?, updated_at=NOW()
                 WHERE id=?"
            );
            $s->execute([
                $requestType,
                $impact,
                $urgency,
                $priority,
                $prioritySource,
                $slaId,
                $firstDue,
                $resolutionDue,
                $id,
            ]);

            $event = $pdo->prepare(
                "INSERT INTO ticket_events
                    (ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at)
                 VALUES(?,'CLASSIFICATION_CHANGED',?,'USER',?,?,?,NOW())"
            );
            $event->execute([
                $id,
                (int)Auth::id(),
                json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode(['priority_override' => $prioritySource === 'MANUAL'], JSON_UNESCAPED_UNICODE),
            ]);
        });

        Audit::log('TICKET_CLASSIFICATION_CHANGED', 'ticket', $id, $before, $after);
        Flash::set(
            'Clasificación actualizada. Prioridad: '.TicketClassificationService::priorityLabel($priority).'.',
            'success'
        );
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);
        exit;
    }

    private function slaFor(PDO $pdo, int $categoryId, string $priority, string $createdAt): array
    {
        $s = $pdo->prepare(
            "SELECT id,first_response_minutes,resolution_minutes
             FROM sla_policies
             WHERE is_active=1 AND priority=? AND (category_id=? OR category_id IS NULL)
             ORDER BY category_id IS NULL ASC,id ASC
             LIMIT 1"
        );
        $s->execute([$priority, $categoryId]);
        $policy = $s->fetch();

        if (!$policy) {
            return [null, null, null];
        }

        $base = strtotime($createdAt);
        if ($base === false) {
            $base = time();
        }

        return [
            (int)$policy['id'],
            date('Y-m-d H:i:s', $base + (int)$policy['first_response_minutes'] * 60),
            date('Y-m-d H:i:s', $base + (int)$policy['resolution_minutes'] * 60),
        ];
    }

    private function back(int $id, string $message): void
    {
        Flash::set($message, 'warning');
        $target = $id > 0 ? APP_BASE_URL.'/tickets/view?id='.$id : APP_BASE_URL.'/tickets/queue';
        header('Location: '.$target);
        exit;
    }
}
