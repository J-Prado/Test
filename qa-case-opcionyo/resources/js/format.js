// Human-friendly date/time in Spanish locale.
export function formatDateTime(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString('es-ES', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function statusLabel(status) {
    return {
        active: 'Activa',
        incomplete: 'Procesando',
        past_due: 'Pago vencido',
        canceled: 'Cancelada',
    }[status] || status || '—';
}
