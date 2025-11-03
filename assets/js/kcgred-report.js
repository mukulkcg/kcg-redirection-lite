document.addEventListener('DOMContentLoaded', function() {
    const canvas_element = document.getElementById('kcgred-redirects-chart');
    
    if(canvas_element) {
        const reports = canvas_element.getAttribute('data-reports');
        var reports_data = reports.split(',').map(Number); 
        const ctx = canvas_element.getContext('2d');
        if (!ctx) {
            return;
        }
        
        const data = reports_data;
        const total = data.reduce((a, b) => a + b, 0);
        
        const myChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Total Redirects', 'Active', 'Total Hits'],
                datasets: [{
                    label: '#',
                    data: data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                    ],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return {
                                            text: `${label}: ${value} (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = context.raw;
                                const percentage = ((value / total) * 100).toFixed(1);
                                label += value + ' (' + percentage + '%)';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});