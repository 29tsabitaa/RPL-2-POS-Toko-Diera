// Tampilkan dialog konfirmasi saat pengguna memilih logout.
const logoutModal = document.getElementById('logoutModal');

document.querySelectorAll('[data-logout]').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        logoutModal.classList.add('show');
    });
});

document.getElementById('lgBatal')?.addEventListener('click', () => {
    logoutModal.classList.remove('show');
});

logoutModal?.addEventListener('click', (event) => {
    if (event.target === logoutModal) {
        logoutModal.classList.remove('show');
    }
});

// Minta konfirmasi sebelum form bertanda data-confirm dikirim.
document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
