<?php
$content = file_get_contents('backend/routes/web.php');
// find the last ];
$pos = strrpos($content, '];');
if ($pos !== false) {
    $insert = "
    // ==========================================
    // Member 4: Operations & Logistics
    // ==========================================
    '/operations/venues' => function() { return ['view' => 'operations/venues/index']; },
    '/operations/venues/create' => function() { return ['view' => 'operations/venues/create']; },
    '/operations/venues/{id}' => function(\$id) { return ['view' => 'operations/venues/show', 'data' => ['id' => \$id]]; },
    '/operations/venues/{id}/edit' => function(\$id) { return ['view' => 'operations/venues/edit', 'data' => ['id' => \$id]]; },
    '/operations/venues/{venueId}/facilities' => function(\$venueId) { return ['view' => 'operations/facilities/index', 'data' => ['venueId' => \$venueId]]; },
    '/operations/venues/{venueId}/facilities/create' => function(\$venueId) { return ['view' => 'operations/facilities/create', 'data' => ['venueId' => \$venueId]]; },
    '/operations/venues/{venueId}/facilities/{id}/edit' => function(\$venueId, \$id) { return ['view' => 'operations/facilities/edit', 'data' => ['venueId' => \$venueId, 'id' => \$id]]; },
";
    $content = substr_replace($content, $insert . "];\n", $pos, 2);
    file_put_contents('backend/routes/web.php', $content);
}
