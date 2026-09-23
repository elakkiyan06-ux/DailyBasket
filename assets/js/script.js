// assets/js/script.js - Interactive client-side logic for FreshCart

document.addEventListener('DOMContentLoaded', function () {
    // 1. Quantity Adjuster Controls
    document.querySelectorAll('.fc-qty-group').forEach(function (group) {
        const input = group.querySelector('.fc-qty-input');
        const btnDec = group.querySelector('.fc-qty-btn[data-action="dec"]');
        const btnInc = group.querySelector('.fc-qty-btn[data-action="inc"]');

        if (!input) return;

        const min = parseInt(input.getAttribute('min') || '1', 10);
        const max = parseInt(input.getAttribute('max') || '999', 10);

        if (btnDec) {
            btnDec.addEventListener('click', function (e) {
                e.preventDefault();
                let currentVal = parseInt(input.value, 10) || min;
                if (currentVal > min) {
                    input.value = currentVal - 1;
                    triggerChange(input);
                }
            });
        }

        if (btnInc) {
            btnInc.addEventListener('click', function (e) {
                e.preventDefault();
                let currentVal = parseInt(input.value, 10) || min;
                if (currentVal < max) {
                    input.value = currentVal + 1;
                    triggerChange(input);
                } else {
                    showToast('Max available stock reached (' + max + ')', 'warning');
                }
            });
        }

        input.addEventListener('change', function () {
            let val = parseInt(input.value, 10);
            if (isNaN(val) || val < min) val = min;
            if (val > max) {
                val = max;
                showToast('Quantity adjusted to max available stock (' + max + ')', 'warning');
            }
            input.value = val;
        });
    });

    function triggerChange(element) {
        const event = new Event('change', { bubbles: true });
        element.dispatchEvent(event);
    }

    // 2. Destructive Action Confirmation
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 3. Auto-dismiss alerts after 5 seconds
    setTimeout(function () {
        document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            } catch (err) {}
        });
    }, 5000);
});

// Toast notification helper
function showToast(message, type = 'info') {
    let container = document.getElementById('fc-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'fc-toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }

    const toastId = 'toast-' + Math.random().toString(36).substr(2, 9);
    const bgClass = type === 'success' ? 'bg-success text-white' :
                    type === 'warning' ? 'bg-warning text-dark' :
                    type === 'danger'  ? 'bg-danger text-white' : 'bg-primary text-white';

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-medium">
                    ${message}
                </div>
                <button type="button" class="btn-close ${type !== 'warning' ? 'btn-close-white' : ''} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    if (toastEl && window.bootstrap) {
        const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }
}
