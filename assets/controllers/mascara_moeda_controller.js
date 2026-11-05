import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if (this.element.value) {
            this.element.value = this.formatar(Math.round(parseFloat(this.element.value) * 100));
        }
        this.aoEnviar = () => { this.element.value = this.paraDecimal(); };
        this.element.form?.addEventListener('submit', this.aoEnviar);
    }

    disconnect() {
        this.element.form?.removeEventListener('submit', this.aoEnviar);
    }

    mascarar() {
        const centavos = parseInt(this.element.value.replace(/\D/g, ''), 10);
        this.element.value = Number.isNaN(centavos) ? '' : this.formatar(centavos);
    }

    formatar(centavos) {
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(centavos / 100);
    }

    paraDecimal() {
        const digitos = this.element.value.replace(/\D/g, '');
        return digitos ? (parseInt(digitos, 10) / 100).toFixed(2) : '';
    }
}