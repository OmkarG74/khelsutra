<?php
$router->get('/api/v1/events', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\EventController(new \App\Services\Operations\EventService());
    return $c->index(request());
});
$router->post('/api/v1/events', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\EventController(new \App\Services\Operations\EventService());
    return $c->store(app(\App\Http\Requests\BaseFormRequest::class));
});
$router->post('/api/v1/events/{id}/participants', function ($id) {
    $c = new \App\Http\Controllers\Api\V1\Operations\EventController(new \App\Services\Operations\EventService());
    return $c->addParticipant(app(\App\Http\Requests\BaseFormRequest::class), $id);
});

$router->get('/api/v1/school-activities', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\SchoolActivityController(new \App\Services\Operations\EventService());
    return $c->index(request());
});
$router->post('/api/v1/school-activities', function () {
    $c = new \App\Http\Controllers\Api\V1\Operations\SchoolActivityController(new \App\Services\Operations\EventService());
    return $c->store(app(\App\Http\Requests\BaseFormRequest::class));
});

$router->get('/operations/events', function () {
    ob_start();
    include __DIR__ . '/backend/resources/views/operations/events/index.blade.php';
    return ob_get_clean();
});

$router->get('/operations/school-activities', function () {
    ob_start();
    include __DIR__ . '/backend/resources/views/operations/school-activities/index.blade.php';
    return ob_get_clean();
});
