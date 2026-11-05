import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    connect() {
        this.tooltips = Array.from(this.element.querySelectorAll('[data-sidebar-tooltip]'))
            .map((el) => new Tooltip(el, { placement: 'right', trigger: 'hover', container: 'body' }));
        this.sync();
    }

    disconnect() {
        this.tooltips.forEach((tooltip) => tooltip.dispose());
        this.tooltips = [];
    }

    toggle() {
        document.documentElement.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem('sidebar-collapsed', this.collapsed() ? '1' : '0');
        } catch (e) {
        }
        this.sync();
    }

    sync() {
        const collapsed = this.collapsed();

        this.tooltips.forEach((tooltip) => {
            const isToggle = tooltip._element.classList.contains('sidebar-toggle');
            if (collapsed || isToggle) {
                tooltip.enable();
            } else {
                tooltip.hide();
                tooltip.disable();
            }
        });

        const button = this.element.querySelector('.sidebar-toggle');
        if (!button) {
            return;
        }

        const label = collapsed ? 'Expandir menu' : 'Recolher menu';
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        button.setAttribute('aria-label', label);

        const toggleTooltip = Tooltip.getInstance(button);
        if (toggleTooltip) {
            toggleTooltip.setContent({ '.tooltip-inner': label });
        }
    }

    collapsed() {
        return document.documentElement.classList.contains('sidebar-collapsed');
    }
}