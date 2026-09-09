<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$required=[
    'bootstrap.php','config/config.php','config/local.php.example','database/INSTALAR_FASE1.sql',
    'database/ACTUALIZAR_FLUJO_ESPERA_P1.sql','database/ACTUALIZAR_ITSM_PROBLEMAS_CONOCIMIENTO_V2.sql','database/VERIFICAR_ESTABILIDAD_V2.sql',
    'app/Services/AuthService.php','app/Services/ScopeService.php','app/Services/ProblemService.php','app/Services/SolutionSuggestionService.php','app/Core/Auth.php',
    'app/Controllers/SearchController.php','app/Controllers/WorkflowController.php','app/Controllers/ProblemController.php','app/Controllers/KnowledgeController.php',
    'app/Views/search/index.php','app/Views/problems/index.php','app/Views/problems/form.php','app/Views/problems/show.php',
    'app/Views/knowledge/index.php','app/Views/knowledge/form.php','app/Views/knowledge/show.php','public/index.php','HELPDESK_ADMIN.bat'
];
$ok=true;
foreach($required as $f){$exists=is_file($root.'/'.$f);echo ($exists?'[OK] ':'[FALTA] ').$f.PHP_EOL;$ok=$ok&&$exists;}

$checks=[
    'public/index.php'=>['/problems','/knowledge'],
    'app/Views/shared/app_start.php'=>['Problemas conocidos','Base de conocimiento'],
    'app/Views/tickets/show.php'=>['Posibles soluciones','Crear artículo desde solución','Problema conocido relacionado'],
    'app/Controllers/ProblemController.php'=>['problems.manage','PROBLEM_LINKED'],
    'app/Controllers/KnowledgeController.php'=>['knowledge.manage','KNOWLEDGE_CREATED'],
];
foreach($checks as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle){$found=is_string($content)&&str_contains($content,$needle);echo ($found?'[OK] ':'[FALTA] ').$file.' contiene '.$needle.PHP_EOL;$ok=$ok&&$found;}}
exit($ok?0:1);
