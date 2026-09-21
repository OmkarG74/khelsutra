<?php
$router->get('/api/v1/accommodations', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\AccommodationController(new \App\Services\Operations\AccommodationService());
    return $c->index(request());
});
$router->post('/api/v1/accommodations', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\AccommodationController(new \App\Services\Operations\AccommodationService());
    return $c->store(app(\App\Http\Requests\BaseFormRequest::class));
});
$router->post('/api/v1/accommodations/{id}/rooms', function ($id) {
    $c = new \App\Http\Controllers\Api\V1\Operations\AccommodationController(new \App\Services\Operations\AccommodationService());
    return $c->storeRoom(app(\App\Http\Requests\BaseFormRequest::class), $id);
});

$router->get('/api/v1/room-allocations', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\RoomAllocationController(new \App\Services\Operations\RoomAllocationService());
    return $c->index(request());
});
$router->post('/api/v1/room-allocations', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\RoomAllocationController(new \App\Services\Operations\RoomAllocationService());
    return $c->store(app(\App\Http\Requests\BaseFormRequest::class));
});

$router->get('/operations/accommodation', function () {
    ob_start();
    include __DIR__ . '/backend/resources/views/operations/accommodation/index.blade.php';
    return ob_get_clean();
});
