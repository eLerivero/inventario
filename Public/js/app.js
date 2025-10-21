// Funciones globales de JavaScript
document.addEventListener('DOMContentLoaded', function () {

    // Confirmación para eliminaciones
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (!confirm('¿Estás seguro de que deseas eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });

    // Formato automático de moneda
    const currencyInputs = document.querySelectorAll('input[data-currency]');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function () {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });

    // Búsqueda en tiempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value;
                if (searchTerm.length >= 2) {
                    performSearch(searchTerm);
                } else if (searchTerm.length === 0) {
                    location.reload();
                }
            }, 500);
        });
    }
});

function performSearch(searchTerm) {
    // Esta función será sobrescrita por páginas específicas
    console.log('Buscando:', searchTerm);
}

// Funciones de utilidad
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function showLoading() {
    document.body.classList.add('loading');
}

function hideLoading() {
    document.body.classList.remove('loading');
}