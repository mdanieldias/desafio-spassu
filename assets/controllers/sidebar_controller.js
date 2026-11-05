import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.sync();
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
        const button = this.element.querySelector('.sidebar-toggle');
        if (!button) {
            return;
        }

        const collapsed = this.collapsed();
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        button.setAttribute('aria-label', collapsed ? 'Expandir menu' : 'Recolher menu');
    }

    collapsed() {
        return document.documentElement.classList.contains('sidebar-collapsed');
    }
}
