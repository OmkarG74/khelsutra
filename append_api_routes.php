<?php
$content = file_get_contents('backend/routes/api.php');
$pos = strrpos($content, '// 13. Fallback for other unassigned modules');
if ($pos !== false) {
    $insert = "
    // Member 4: Operations Bookings
    if (\$uri === '/api/v1/bookings' && \$method === 'GET') {
        \$controller = new \App\Http\Controllers\Api\V1\Operations\VenueBookingController();
        return \$controller->index(\$orgId, \$requestData);
    }
    if (\$uri === '/api/v1/bookings' && \$method === 'POST') {
        \$controller = new \App\Http\Controllers\Api\V1\Operations\VenueBookingController();
        return \$controller->store(\$orgId, \$requestData);
    }
    if (preg_match('#^/api/v1/bookings/(\d+)/cancel$#', \$uri, \$matches) && \$method === 'POST') {
        \$controller = new \App\Http\Controllers\Api\V1\Operations\VenueBookingController();
        return \$controller->cancel(\$orgId, (int)\$matches[1], \$requestData);
    }
    if (preg_match('#^/api/v1/venues/(\d+)/availability$#', \$uri, \$matches) && \$method === 'GET') {
        \$controller = new \App\Http\Controllers\Api\V1\Operations\VenueBookingController();
        return \$controller->availability(\$orgId, (int)\$matches[1], \$requestData);
    }
";
    $content = substr_replace($content, $insert . "\n    // 13. Fallback for other unassigned modules", $pos, strlen('// 13. Fallback for other unassigned modules'));
    file_put_contents('backend/routes/api.php', $content);
}
