// assets/js/chart-config.js
function createWeightChart(labels, weights) {
    const ctx = document.getElementById('weightChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Weight (kg)',
                data: weights,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: false } }
        }
    });
}

function createMacroChart(protein, carbs, fat) {
    const ctx = document.getElementById('macroChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Protein', 'Carbs', 'Fat'],
            datasets: [{
                data: [protein, carbs, fat],
                backgroundColor: ['#dc3545', '#ffc107', '#198754']
            }]
        },
        options: { responsive: true }
    });
}