<?php include __DIR__ . '/../../layouts/app.blade.php'; ?>

<div class="container mt-4">
    <h2>School Activities</h2>
    <div id="school-activities-list"></div>
</div>

<script>
    fetch('/api/v1/school-activities')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('school-activities-list');
            list.innerHTML = '<ul>' + data.data.map(i => `<li>${i.school_name} - ${i.activity_name} (${i.activity_date})</li>`).join('') + '</ul>';
        });
</script>
