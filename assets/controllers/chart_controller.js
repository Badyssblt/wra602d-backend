import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    static values = { type: String, data: Object };

    connect() {
        this.chart = new Chart(this.element, {
            type: this.typeValue || 'line',
            data: this.dataValue,
            options: { responsive: true, maintainAspectRatio: false },
        });
    }

    disconnect() {
        this.chart?.destroy();
    }
}
