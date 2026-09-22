<?php include __DIR__ . '/../../layouts/app.blade.php'; ?>

<div class="container mt-4">
    <h2>Transport (Vehicles & Trips)</h2>
    <div id="transport-list"></div>
</div>

<script>
    fetch('/api/v1/vehicles')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('transport-list');
            list.innerHTML = '<h4>Vehicles</h4><ul>' + data.data.map(i => `<li>${i.vehicle_number} (${i.status})</li>`).join('') + '</ul>';
        });
</script>
