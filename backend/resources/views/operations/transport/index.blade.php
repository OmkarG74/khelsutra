<?php include __DIR__ . '/../../layouts/app.blade.php'; ?>

<div class="container mt-4">
    <h2>Transport (Vehicles & Trips)</h2>
    <div id="transport-list"></div>
    <div id="trips-list" class="mt-4"></div>
</div>

<script>
    function fetchLocation(id) {
        fetch('/api/v1/vehicles/' + id + '/location')
            .then(res => res.json())
            .then(res => {
                if(res.success && res.data) {
                    alert('Live Location: Lat ' + res.data.latitude + ', Lon ' + res.data.longitude);
                } else {
                    alert('Location not available or offline.');
                }
            });
    }

    function fetchRouteHistory(id) {
        fetch('/api/v1/trips/' + id + '/route-history')
            .then(res => res.json())
            .then(res => {
                if(res.success && res.data && res.data.length > 0) {
                    alert('Route History: ' + res.data.length + ' points found.');
                } else {
                    alert('No route history found for this trip.');
                }
            });
    }

    fetch('/api/v1/vehicles')
        .then(res => res.json())
        .then(res => {
            if(res.data && res.data.data) {
                const list = document.getElementById('transport-list');
                list.innerHTML = '<h4>Vehicles</h4><ul class="list-group">' + res.data.data.map(i => 
                    `<li class="list-group-item d-flex justify-content-between align-items-center">
                        ${i.vehicle_number} (${i.status})
                        <button class="btn btn-sm btn-outline-primary" onclick="fetchLocation(${i.id})">Live Location</button>
                    </li>`
                ).join('') + '</ul>';
            }
        });

    fetch('/api/v1/trips')
        .then(res => res.json())
        .then(res => {
            if(res.data && res.data.data) {
                const list = document.getElementById('trips-list');
                list.innerHTML = '<h4>Trips</h4><ul class="list-group">' + res.data.data.map(i => 
                    `<li class="list-group-item d-flex justify-content-between align-items-center">
                        Trip ${i.trip_reference} on ${i.trip_date}
                        <button class="btn btn-sm btn-outline-secondary" onclick="fetchRouteHistory(${i.id})">Route History</button>
                    </li>`
                ).join('') + '</ul>';
            }
        });
</script>
