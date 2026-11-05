import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    connect() {
        this.tooltips = Array.from(this.element.querySelectorAll('[data-sidebar-tooltip]'))
            .map((el) => new Tooltip(el, { placement: 'right', trigger: 'hover', container: 'body' }));

        this.desktop = window.matchMedia('(min-width: 768px)');
        this.aoMudarTela = () => this.sync();
        this.desktop.addEventListener('change', this.aoMudarTela);

        this.sync();
    }

    disconnect() {
        this.desktop.removeEventListener('change', this.aoMudarTela);
        this.tooltips.forEach((tooltip) => tooltip.dispose());
        this.tooltips = [];
    }

    toggle() {
        if (this.desktop.matches) {
            document.documentElement.classList.toggle('sidebar-collapsed');
            try {
                localStorage.setItem('sidebar-collapsed', this.collapsed() ? '1' : '0');
            } catch (e) {
            }
        } else {
            this.element.classList.toggle('is-open');
        }
        this.sync();
    }

    sync() {
        const collapsed = this.collapsed();
        const tooltipsAtivos = collapsed && this.desktop.matches;

        this.tooltips.forEach((tooltip) => {
            const isToggle = tooltip._element.classList.contains('sidebar-toggle');
            if (tooltipsAtivos || (isToggle && this.desktop.matches)) {
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

        const aberto = this.desktop.matches ? !collapsed : this.element.classList.contains('is-open');
        const label = this.desktop.matches
            ? (collapsed ? 'Expandir menu' : 'Recolher menu')
            : (aberto ? 'Fechar menu' : 'Abrir menu');
        button.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        button.setAttribute('aria-label', label);

        const toggleTooltip = Tooltip.getInstance(button);
        if (toggleTooltip && this.desktop.matches) {
            toggleTooltip.setContent({ '.tooltip-inner': label });
        }
    }

    collapsed() {
        return document.documentElement.classList.contains('sidebar-collapsed');
    }
}