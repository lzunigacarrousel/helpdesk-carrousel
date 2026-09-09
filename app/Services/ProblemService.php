<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use PDO;

final class ProblemService
{
    public function linkTicket(int $problemId,int $ticketId,?int $userId): bool
    {
        $pdo=Database::pdo();
        $stmt=$pdo->prepare("INSERT IGNORE INTO problem_occurrences(problem_id,ticket_id,linked_by,confidence,created_at) VALUES(?,?,?,'MANUAL',NOW())");
        $stmt->execute([$problemId,$ticketId,$userId]);
        $created=$stmt->rowCount()>0;
        $this->syncStats($problemId);
        return $created;
    }

    public function unlinkTicket(int $problemId,int $ticketId): bool
    {
        $pdo=Database::pdo();
        $stmt=$pdo->prepare('DELETE FROM problem_occurrences WHERE problem_id=? AND ticket_id=?');
        $stmt->execute([$problemId,$ticketId]);
        $deleted=$stmt->rowCount()>0;
        $this->syncStats($problemId);
        return $deleted;
    }

    public function syncStats(int $problemId): void
    {
        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT COUNT(*) occurrences,MIN(t.created_at) first_seen,MAX(t.created_at) last_seen
            FROM problem_occurrences po JOIN tickets t ON t.id=po.ticket_id
            WHERE po.problem_id=? AND t.deleted_at IS NULL");
        $q->execute([$problemId]);
        $row=$q->fetch()?:['occurrences'=>0,'first_seen'=>null,'last_seen'=>null];
        $u=$pdo->prepare('UPDATE known_problems SET occurrence_count=?,first_seen_at=?,last_seen_at=?,updated_at=NOW() WHERE id=?');
        $u->execute([(int)$row['occurrences'],$row['first_seen'],$row['last_seen'],$problemId]);
    }

    public function resolveTickets(string $input): array
    {
        $tokens=preg_split('/[\s,;]+/',trim($input),-1,PREG_SPLIT_NO_EMPTY)?:[];
        if(!$tokens)return [];
        $pdo=Database::pdo();$ids=[];
        $byNumber=$pdo->prepare('SELECT id FROM tickets WHERE ticket_number=? AND deleted_at IS NULL LIMIT 1');
        $byId=$pdo->prepare('SELECT id FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');
        foreach(array_slice($tokens,0,30) as $token){
            $id=0;
            if(ctype_digit($token)){$byId->execute([(int)$token]);$id=(int)($byId->fetchColumn()?:0);}else{$byNumber->execute([strtoupper($token)]);$id=(int)($byNumber->fetchColumn()?:0);}
            if($id>0)$ids[$id]=$id;
        }
        return array_values($ids);
    }
}
