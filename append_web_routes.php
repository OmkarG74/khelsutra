<?php
$content = file_get_contents('backend/routes/web.php');
$pos = strrpos($content, '];');
if ($pos !== false) {
    $insert = "
    '/operations/maintenance' => function() { return ['view' => 'operations/maintenance/index']; },
    '/operations/housekeeping' => function() { return ['view' => 'operations/housekeeping/index']; },
";
    $content = substr_replace($content, $insert . "];\n", $pos, 2);
    file_put_contents('backend/routes/web.php', $content);
}
