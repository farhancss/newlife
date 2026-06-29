/**
 * Renders the admin dashboard ApexCharts from data embedded on the chart
 * containers (data-* attributes). ApexCharts is imported lazily so it is only
 * shipped to pages that actually render a chart.
 */

const FONT_FAMILY = 'Outfit, sans-serif';

function parseJson(value, fallback) {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch {
        return fallback;
    }
}

function renderSignupTrend(ApexCharts, el) {
    const categories = parseJson(el.dataset.categories, []);
    const data = parseJson(el.dataset.values, []);

    const chart = new ApexCharts(el, {
        series: [{ name: 'New students', data }],
        colors: ['#465fff'],
        chart: {
            fontFamily: FONT_FAMILY,
            type: 'area',
            height: 280,
            toolbar: { show: false },
        },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] },
        },
        dataLabels: { enabled: false },
        markers: { size: 0, hover: { size: 5 } },
        grid: {
            borderColor: '#f2f4f7',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
        },
        xaxis: {
            categories,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#667085', fontSize: '12px' } },
        },
        yaxis: {
            labels: { style: { colors: '#667085', fontSize: '12px' }, formatter: (val) => Math.round(val) },
            min: 0,
            forceNiceScale: true,
        },
        tooltip: { x: { show: true }, y: { formatter: (val) => `${val} student${val === 1 ? '' : 's'}` } },
    });

    chart.render();
    return chart;
}

function renderPackageMix(ApexCharts, el) {
    const labels = parseJson(el.dataset.labels, []);
    const series = parseJson(el.dataset.series, []);
    const colors = parseJson(el.dataset.colors, []);
    const total = series.reduce((sum, value) => sum + value, 0);

    const chart = new ApexCharts(el, {
        series,
        labels,
        colors,
        chart: { fontFamily: FONT_FAMILY, type: 'donut', height: 280 },
        stroke: { width: 0 },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            fontFamily: FONT_FAMILY,
            labels: { colors: '#667085' },
            markers: { radius: 99 },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Students',
                            color: '#667085',
                            fontSize: '13px',
                            formatter: () => `${total}`,
                        },
                        value: { color: '#101828', fontSize: '24px', fontWeight: 600 },
                    },
                },
            },
        },
        tooltip: { y: { formatter: (val) => `${val} student${val === 1 ? '' : 's'}` } },
        noData: { text: 'No students yet', style: { color: '#667085' } },
    });

    chart.render();
    return chart;
}

const RENDERERS = {
    'signup-trend': renderSignupTrend,
    'package-mix': renderPackageMix,
};

export function initDashboardCharts() {
    const elements = document.querySelectorAll('[data-chart]');
    if (!elements.length) {
        return;
    }

    import('apexcharts').then(({ default: ApexCharts }) => {
        elements.forEach((el) => {
            const renderer = RENDERERS[el.dataset.chart];
            if (renderer) {
                renderer(ApexCharts, el);
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
    initDashboardCharts();
}

export default initDashboardCharts;
