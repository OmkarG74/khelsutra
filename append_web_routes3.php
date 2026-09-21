<?php
$router->get('/api/v1/vehicles', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\VehicleController(new \App\Services\Operations\VehicleService());
    return $c->index(request());
});
$router->post('/api/v1/vehicles', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\VehicleController(new \App\Services\Operations\VehicleService());
    return $c->store(app(\App\Http\Requests\BaseFormRequest::class));
});

$router->get('/api/v1/trips', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\TransportTripController(new \App\Services\Operations\TransportTripService());
    return $c->index(request());
});
$router->post('/api/v1/trips', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\TransportTripController(new \App\Services\Operations\TransportTripService());
    return $c->store(app(\App\Http\Requests\BaseFormRequest::class));
});
$router->post('/api/v1/trips/{id}/passengers', function ($id) {
    $c = new \App\Http\Controllers\Api\V1\Operations\TransportTripController(new \App\Services\Operations\TransportTripService());
    return $c->addPassenger(app(\App\Http\Requests\BaseFormRequest::class), $id);
});

$router->get('/operations/transport', function () {
    ob_start();
    include __DIR__ . '/backend/resources/views/operations/transport/index.blade.php';
    return ob_get_clean();
});
