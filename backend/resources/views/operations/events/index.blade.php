<?php include __DIR__ . '/../../layouts/app.blade.php'; ?>

<div class="container mt-4">
    <h2>Events</h2>
    <div id="events-list"></div>
</div>

<script>
    fetch('/api/v1/events')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('events-list');
            list.innerHTML = '<ul>' + data.data.map(i => `<li>${i.event_reference} - ${i.name} (${i.status})</li>`).join('') + '</ul>';
        });
</script>
