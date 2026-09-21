<?php include __DIR__ . '/../../layouts/app.blade.php'; ?>

<div class="container mt-4">
    <h2>Accommodation</h2>
    <div id="acc-list"></div>
</div>

<script>
    fetch('/api/v1/accommodations')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('acc-list');
            list.innerHTML = '<ul>' + data.data.map(i => `<li>${i.name} (${i.status})</li>`).join('') + '</ul>';
        });
</script>
