<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class KnowledgeReferenceService
{
    public function useReference(int $ticketId,string $type,int $referenceId,int $userId): array
    {
        if($ticketId<=0||$referenceId<=0)throw new RuntimeException('Referencia no válida.');
        $type=strtoupper(trim($type));
        if(!in_array($type,['KNOWLEDGE','PROBLEM','TICKET'],true)){
            throw new RuntimeException('Tipo de referencia no válido.');
        }

        return Database::transaction(function(PDO $pdo)use($ticketId,$type,$referenceId,$userId):array{
            $ticket=$pdo->prepare('SELECT id,status FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1 FOR UPDATE');
            $ticket->execute([$ticketId]);
            $target=$ticket->fetch();
            if(!$target)throw new RuntimeException('Ticket no encontrado.');
            if(in_array((string)$target['status'],['CLOSED','CANCELLED'],true)){
                throw new RuntimeException('Este caso ya no admite nuevas referencias.');
            }

            $rootCause='';$solution='';$prevention='';
            $articleId=null;$revisionId=null;$problemId=null;$sourceTicketId=null;

            if($type==='KNOWLEDGE'){
                $q=$pdo->prepare(
                    "SELECT ka.id article_id,ka.current_internal_revision_id revision_id,
                            kr.content,kr.summary
                     FROM knowledge_articles ka
                     JOIN knowledge_revisions kr ON kr.id=ka.current_internal_revision_id
                     WHERE ka.id=? AND ka.lifecycle_status='ACTIVE' LIMIT 1"
                );
                $q->execute([$referenceId]);
                $row=$q->fetch();
                if(!$row)throw new RuntimeException('El artículo no tiene una versión interna disponible.');
                $sections=self::extractSections((string)$row['content']);
                $rootCause=$sections['root_cause'];
                $solution=$sections['solution'];
                $prevention=$sections['prevention'];
                if($solution==='')$solution=trim((string)($row['summary']??''));
                $articleId=(int)$row['article_id'];
                $revisionId=(int)$row['revision_id'];
            }elseif($type==='PROBLEM'){
                $q=$pdo->prepare(
                    'SELECT id,root_cause,workaround,permanent_solution FROM known_problems WHERE id=? LIMIT 1'
                );
                $q->execute([$referenceId]);
                $row=$q->fetch();
                if(!$row)throw new RuntimeException('Problema conocido no encontrado.');
                $rootCause=trim((string)($row['root_cause']??''));
                $solution=trim((string)($row['permanent_solution']??''));
                if($solution==='')$solution=trim((string)($row['workaround']??''));
                $problemId=(int)$row['id'];
            }else{
                $q=$pdo->prepare(
                    "SELECT t.id,tr.root_cause,tr.solution_applied,tr.preventive_action
                     FROM tickets t
                     JOIN ticket_resolutions tr ON tr.ticket_id=t.id
                     WHERE t.id=? AND t.deleted_at IS NULL
                       AND t.status IN('RESOLVED','CLOSED')
                     LIMIT 1"
                );
                $q->execute([$referenceId]);
                $row=$q->fetch();
                if(!$row)throw new RuntimeException('El caso seleccionado no tiene una solución reutilizable.');
                $rootCause=trim((string)($row['root_cause']??''));
                $solution=trim((string)($row['solution_applied']??''));
                $prevention=trim((string)($row['preventive_action']??''));
                $sourceTicketId=(int)$row['id'];
            }

            $appliedRoot=$rootCause!==''?1:0;
            $appliedSolution=$solution!==''?1:0;
            $appliedPrevention=$prevention!==''?1:0;

            $insert=$pdo->prepare(
                "INSERT INTO ticket_resolution_references(
                    ticket_id,reference_type,knowledge_article_id,knowledge_revision_id,
                    problem_id,source_ticket_id,used_by_user_id,used_at,
                    applied_root_cause,applied_solution,applied_prevention
                 ) VALUES(?,?,?,?,?,?,?,NOW(),?,?,?)"
            );
            $insert->execute([
                $ticketId,$type,$articleId,$revisionId,$problemId,$sourceTicketId,$userId,
                $appliedRoot,$appliedSolution,$appliedPrevention
            ]);
            $traceId=(int)$pdo->lastInsertId();

            $eventType=[
                'KNOWLEDGE'=>'KNOWLEDGE_REFERENCE_USED',
                'PROBLEM'=>'PROBLEM_REFERENCE_USED',
                'TICKET'=>'TICKET_REFERENCE_USED',
            ][$type];
            $metadata=[
                'reference_trace_id'=>$traceId,
                'reference_type'=>$type,
                'knowledge_article_id'=>$articleId,
                'knowledge_revision_id'=>$revisionId,
                'problem_id'=>$problemId,
                'source_ticket_id'=>$sourceTicketId,
                'applied_root_cause'=>(bool)$appliedRoot,
                'applied_solution'=>(bool)$appliedSolution,
                'applied_prevention'=>(bool)$appliedPrevention,
            ];
            $pdo->prepare(
                "INSERT INTO ticket_events(
                    ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at
                 ) VALUES(?,?,?,'USER',?,?,NOW())"
            )->execute([
                $ticketId,$eventType,$userId,
                json_encode(['reference_type'=>$type],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            ]);

            return[
                'root_cause'=>$rootCause,
                'solution'=>$solution,
                'prevention'=>$prevention,
                'reference'=>[
                    'type'=>$type,
                    'trace_id'=>$traceId,
                    'article_id'=>$articleId,
                    'revision_id'=>$revisionId,
                    'problem_id'=>$problemId,
                    'source_ticket_id'=>$sourceTicketId,
                ],
            ];
        });
    }

    public static function extractSections(string $content): array
    {
        $result=['root_cause'=>'','solution'=>'','prevention'=>''];
        $content=str_replace(["\r\n","\r"],"\n",$content);
        $lines=explode("\n",$content);
        $current=null;
        $buffers=['root_cause'=>[],'solution'=>[],'prevention'=>[]];

        foreach($lines as $line){
            $trim=trim($line);
            $normalized=self::normalizeHeading($trim);
            if(in_array($normalized,['causa','causa raiz','causa raíz'],true)){
                $current='root_cause';continue;
            }
            if(in_array($normalized,['solucion','solución','solucion aplicada','solución aplicada'],true)){
                $current='solution';continue;
            }
            if(in_array($normalized,['prevencion','prevención','accion preventiva','acción preventiva','como evitarlo','cómo evitarlo'],true)){
                $current='prevention';continue;
            }
            if(self::looksLikeHeading($trim)){
                $current=null;continue;
            }
            if($current!==null&&$trim!=='')$buffers[$current][]=$trim;
        }

        foreach($buffers as $key=>$parts)$result[$key]=trim(implode("\n",$parts));
        return $result;
    }

    private static function normalizeHeading(string $value): string
    {
        $value=trim($value," \t\n\r\0\x0B:");
        return mb_strtolower($value);
    }

    private static function looksLikeHeading(string $value): bool
    {
        if($value==='')return false;
        $normalized=self::normalizeHeading($value);
        return in_array($normalized,[
            'problema','sintomas','síntomas','workaround','pasos','validacion','validación',
            'notas','resumen','diagnostico','diagnóstico'
        ],true);
    }
}
