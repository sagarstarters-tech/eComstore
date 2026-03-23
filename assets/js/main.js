$(document).ready(function () {
    // Form Validation Logic
    $('form').on('submit', function (e) {
        let valid = true;
        $(this).find('input[required]').each(function () {
            if ($(this).val() === '') {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!valid) {
            e.preventDefault();
            showToast('danger', 'Please fill in all required fields.');
        }
    });

    $('input[required]').on('input', function () {
        if ($(this).val() !== '') {
            $(this).removeClass('is-invalid');
        }
    });
});

function showToast(type, message) {
    let bgClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-primary');
    let toastHTML = `
        <div class="toast anim-slide-in align-items-center text-white ${bgClass} border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; z-index: 1055;">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    $('body').append(toastHTML);
    setTimeout(() => {
        $('.toast').removeClass('show');
        setTimeout(() => $('.toast').remove(), 300);
    }, 3000);
}
