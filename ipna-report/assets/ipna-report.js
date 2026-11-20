(function () {
    'use strict';
    if (typeof window.ipnaReport === 'undefined' || typeof Chart === 'undefined') {
        return;
    }
    var data = window.ipnaReport;

    Chart.defaults.font.family = 'Vazirmatn, Tahoma, sans-serif';
    Chart.defaults.animation.duration = 500;

    if (document.getElementById('ipna-line') && data.daily) {
        var labels = data.daily.map(function (r) { return r.label || r.d; });
        new Chart(document.getElementById('ipna-line'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'امتیاز',
                    data: data.daily.map(function (r) { return r.score; }),
                    borderColor: '#1a73e8',
                    backgroundColor: 'rgba(26,115,232,0.15)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    if (document.getElementById('ipna-doughnut') && data.category) {
        var colors = ['#1a73e8', '#e8890c', '#2f9e44'];
        new Chart(document.getElementById('ipna-doughnut'), {
            type: 'doughnut',
            data: {
                labels: data.category.map(function (c) { return c.label; }),
                datasets: [{
                    data: data.category.map(function (c) { return c.value; }),
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    if (document.getElementById('ipna-bar') && data.users) {
        new Chart(document.getElementById('ipna-bar'), {
            type: 'bar',
            data: {
                labels: data.users.map(function (u) { return u.name; }),
                datasets: [{
                    data: data.users.map(function (u) { return u.score; }),
                    backgroundColor: 'rgba(26,115,232,0.72)',
                    hoverBackgroundColor: '#1a73e8',
                    borderRadius: 6,
                    barThickness: 20,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return ' ' + c.parsed.x + ' خبر'; } } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#eef1f5' }, title: { display: true, text: 'تعداد خبر' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
})();
