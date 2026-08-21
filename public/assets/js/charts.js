/**
 * IPNA Performance Management System - Chart.js Helper Functions
 * Provides factory functions for creating consistent, themed charts.
 */

'use strict';

const IPNACharts = (() => {

  /* ============================================================
     DEFAULT COLOR PALETTE
     ============================================================ */
  const COLORS = {
    primary: '#1a237e',
    secondary: '#0d47a1',
    accent: '#ff6f00',
    success: '#2e7d32',
    danger: '#c62828',
    warning: '#f57f17',
    info: '#0277bd',
    purple: '#6a1b9a',
    teal: '#00695c',
    pink: '#c2185b',
    cyan: '#00838f',
    lime: '#9e9d24'
  };

  const PALETTE = [
    COLORS.primary,
    COLORS.secondary,
    COLORS.accent,
    COLORS.success,
    COLORS.info,
    COLORS.warning,
    COLORS.purple,
    COLORS.teal,
    COLORS.pink,
    COLORS.danger,
    COLORS.cyan,
    COLORS.lime
  ];

  const PALETTE_ALPHA = PALETTE.map(color => color + '20');

  /* ============================================================
     RTL DEFAULTS
     ============================================================ */
  const RTL_DEFAULTS = {
    locale: 'fa-IR',
    font: {
      family: "'Vazirmatn', 'Tahoma', sans-serif"
    },
    scales: {
      x: {
        rtl: true,
        ticks: {
          font: { family: "'Vazirmatn', 'Tahoma', sans-serif", size: 12 }
        },
        grid: { color: 'rgba(0,0,0,0.06)' }
      },
      y: {
        reverse: false,
        ticks: {
          font: { family: "'Vazirmatn', 'Tahoma', sans-serif", size: 12 }
        },
        grid: { color: 'rgba(0,0,0,0.06)' }
      }
    },
    plugins: {
      legend: {
        rtl: true,
        labels: {
          font: { family: "'Vazirmatn', 'Tahoma', sans-serif", size: 12 },
          usePointStyle: true,
          padding: 16
        }
      },
      tooltip: {
        rtl: true,
        titleFont: { family: "'Vazirmatn', 'Tahoma', sans-serif", size: 13, weight: '600' },
        bodyFont: { family: "'Vazirmatn', 'Tahoma', sans-serif", size: 12 },
        padding: 12,
        cornerRadius: 8,
        backgroundColor: 'rgba(26, 35, 126, 0.9)',
        titleMarginBottom: 8
      }
    }
  };

  /* ============================================================
     RESPONSIVE OPTIONS
     ============================================================ */
  const RESPONSIVE = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
      duration: 800,
      easing: 'easeOutQuart'
    }
  };

  /* ============================================================
     STORE ACTIVE CHART INSTANCES
     ============================================================ */
  const chartInstances = {};

  function destroyChart(id) {
    if (chartInstances[id]) {
      chartInstances[id].destroy();
      delete chartInstances[id];
    }
  }

  function getCanvas(id) {
    const canvas = document.getElementById(id);
    if (!canvas) {
      console.warn(`Chart canvas not found: #${id}`);
      return null;
    }
    return canvas;
  }

  function deepMerge(target, source) {
    const output = { ...target };
    for (const key of Object.keys(source)) {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        output[key] = deepMerge(output[key] || {}, source[key]);
      } else {
        output[key] = source[key];
      }
    }
    return output;
  }

  /* ============================================================
     createLineChart
     ============================================================ */
  function createLineChart(canvasId, labels, datasets, options = {}) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return null;

    destroyChart(canvasId);

    const chartDatasets = (Array.isArray(datasets) ? datasets : [datasets]).map((ds, i) => ({
      label: ds.label || `مجموعه ${i + 1}`,
      data: ds.data || ds,
      borderColor: ds.borderColor || PALETTE[i % PALETTE.length],
      backgroundColor: ds.backgroundColor || PALETTE_ALPHA[i % PALETTE.length],
      borderWidth: ds.borderWidth || 2.5,
      pointRadius: ds.pointRadius ?? 4,
      pointHoverRadius: ds.pointHoverRadius ?? 6,
      pointBackgroundColor: ds.pointBackgroundColor || PALETTE[i % PALETTE.length],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      tension: ds.tension ?? 0.35,
      fill: ds.fill ?? true,
      ...ds
    }));

    const config = {
      type: 'line',
      data: { labels, datasets: chartDatasets },
      options: deepMerge(deepMerge({}, RESPONSIVE), {
        ...RTL_DEFAULTS,
        scales: {
          x: {
            ...RTL_DEFAULTS.scales.x,
            ...(options.scales?.x || {})
          },
          y: {
            ...RTL_DEFAULTS.scales.y,
            beginAtZero: options.beginAtZero ?? true,
            ...(options.scales?.y || {})
          }
        },
        plugins: {
          ...RTL_DEFAULTS.plugins,
          ...(options.plugins || {})
        },
        ...options
      })
    };

    const ctx = canvas.getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, config);
    return chartInstances[canvasId];
  }

  /* ============================================================
     createBarChart
     ============================================================ */
  function createBarChart(canvasId, labels, data, options = {}) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return null;

    destroyChart(canvasId);

    let chartDatasets;
    if (Array.isArray(data) && data.length > 0 && Array.isArray(data[0])) {
      chartDatasets = data.map((d, i) => ({
        label: options.datasetLabels?.[i] || `مجموعه ${i + 1}`,
        data: d,
        backgroundColor: options.colors?.[i] || PALETTE[i % PALETTE.length] + 'CC',
        borderColor: options.borderColors?.[i] || PALETTE[i % PALETTE.length],
        borderWidth: 1,
        borderRadius: 6,
        borderSkipped: false,
        maxBarThickness: options.maxBarThickness || 50,
        ...(options.datasetOptions?.[i] || {})
      }));
    } else {
      chartDatasets = [{
        label: options.label || 'مقدار',
        data: Array.isArray(data) ? data : Object.values(data),
        backgroundColor: (options.colors || PALETTE.slice(0, (Array.isArray(data) ? data : Object.values(data)).length)).map(c => c + 'CC'),
        borderColor: options.colors || PALETTE.slice(0, (Array.isArray(data) ? data : Object.values(data)).length),
        borderWidth: 1,
        borderRadius: 6,
        borderSkipped: false,
        maxBarThickness: options.maxBarThickness || 50
      }];
    }

    const config = {
      type: 'bar',
      data: { labels, datasets: chartDatasets },
      options: deepMerge(deepMerge({}, RESPONSIVE), {
        ...RTL_DEFAULTS,
        indexAxis: options.horizontal ? 'y' : 'x',
        scales: {
          x: {
            ...RTL_DEFAULTS.scales.x,
            ...(options.horizontal ? {
              beginAtZero: true,
              ...(options.scales?.x || {})
            } : {}),
            ...(options.scales?.x || {})
          },
          y: {
            ...RTL_DEFAULTS.scales.y,
            beginAtZero: true,
            ...(options.horizontal ? {} : {}),
            ...(options.scales?.y || {})
          }
        },
        plugins: {
          ...RTL_DEFAULTS.plugins,
          ...(options.plugins || {})
        },
        ...options
      })
    };

    const ctx = canvas.getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, config);
    return chartInstances[canvasId];
  }

  /* ============================================================
     createDoughnutChart
     ============================================================ */
  function createDoughnutChart(canvasId, labels, data, options = {}) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return null;

    destroyChart(canvasId);

    const colors = options.colors || PALETTE.slice(0, data.length);

    const config = {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data,
          backgroundColor: colors.map(c => c + 'CC'),
          borderColor: colors,
          borderWidth: 2,
          hoverBorderColor: '#ffffff',
          hoverBorderWidth: 3,
          hoverOffset: 8,
          ...(options.datasetOptions || {})
        }]
      },
      options: deepMerge(deepMerge({}, RESPONSIVE), {
        cutout: options.cutout || '65%',
        ...RTL_DEFAULTS,
        scales: undefined,
        plugins: {
          ...RTL_DEFAULTS.plugins,
          legend: {
            ...RTL_DEFAULTS.plugins.legend,
            position: options.legendPosition || 'bottom'
          },
          ...(options.plugins || {})
        },
        ...options
      })
    };

    const ctx = canvas.getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, config);
    return chartInstances[canvasId];
  }

  /* ============================================================
     createRadarChart
     ============================================================ */
  function createRadarChart(canvasId, labels, data, options = {}) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return null;

    destroyChart(canvasId);

    let chartDatasets;
    if (Array.isArray(data) && data.length > 0 && Array.isArray(data[0])) {
      chartDatasets = data.map((d, i) => ({
        label: options.datasetLabels?.[i] || `مجموعه ${i + 1}`,
        data: d,
        backgroundColor: (options.colors?.[i] || PALETTE[i]) + '30',
        borderColor: options.colors?.[i] || PALETTE[i],
        borderWidth: 2,
        pointBackgroundColor: options.colors?.[i] || PALETTE[i],
        pointBorderColor: '#ffffff',
        pointRadius: 4,
        pointHoverRadius: 6,
        ...(options.datasetOptions?.[i] || {})
      }));
    } else {
      chartDatasets = [{
        label: options.label || 'مقدار',
        data: Array.isArray(data) ? data : Object.values(data),
        backgroundColor: (options.colors?.[0] || PALETTE[0]) + '30',
        borderColor: options.colors?.[0] || PALETTE[0],
        borderWidth: 2,
        pointBackgroundColor: options.colors?.[0] || PALETTE[0],
        pointBorderColor: '#ffffff',
        pointRadius: 4,
        pointHoverRadius: 6
      }];
    }

    const config = {
      type: 'radar',
      data: {
        labels,
        datasets: chartDatasets
      },
      options: deepMerge(deepMerge({}, RESPONSIVE), {
        scales: {
          r: {
            rtl: true,
            beginAtZero: true,
            max: options.maxValue || 100,
            ticks: {
              stepSize: options.stepSize || 20,
              font: { family: "'Vazirmatn', 'Tahoma', sans-serif", size: 11 },
              backdropColor: 'transparent'
            },
            pointLabels: {
              font: { family: "'Vazirmatn', 'Tahoma', sans-serif", size: 12 },
              color: '#555'
            },
            grid: {
              color: 'rgba(0,0,0,0.08)'
            },
            angleLines: {
              color: 'rgba(0,0,0,0.08)'
            }
          }
        },
        plugins: {
          ...RTL_DEFAULTS.plugins,
          ...(options.plugins || {})
        },
        ...options
      })
    };

    const ctx = canvas.getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, config);
    return chartInstances[canvasId];
  }

  /* ============================================================
     PUBLIC API
     ============================================================ */
  return {
    createLineChart,
    createBarChart,
    createDoughnutChart,
    createRadarChart,
    destroyChart,
    COLORS,
    PALETTE,
    getChartInstance(id) {
      return chartInstances[id] || null;
    },
    updateChartData(id, labels, data) {
      const chart = chartInstances[id];
      if (!chart) return;
      if (labels) chart.data.labels = labels;
      if (Array.isArray(data)) {
        data.forEach((d, i) => {
          if (chart.data.datasets[i]) {
            chart.data.datasets[i].data = d;
          }
        });
      }
      chart.update();
    },
    resizeAll() {
      Object.values(chartInstances).forEach(chart => chart?.resize());
    }
  };
})();

if (typeof Chart !== 'undefined') {
  Chart.defaults.font.family = "'Vazirmatn', 'Tahoma', sans-serif";
}
