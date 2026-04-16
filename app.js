// HealthLink — Main JavaScript

// ── MODAL ──────────────────────────────────────────────
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// Close modal on backdrop click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.open').forEach(function (m) {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

// ── SIDEBAR TOGGLE ─────────────────────────────────────
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

// ── PASSWORD TOGGLE ────────────────────────────────────
function togglePassword() {
    const input   = document.getElementById('password');
    const icon    = document.getElementById('eyeIcon');
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ── AGE CALCULATOR ─────────────────────────────────────
function calcAge() {
    const bd  = document.getElementById('birthdate');
    const age = document.getElementById('age_field');
    if (!bd || !age || !bd.value) return;
    const today = new Date();
    const birth = new Date(bd.value);
    let years   = today.getFullYear() - birth.getFullYear();
    const m     = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) years--;
    age.value = years >= 0 ? years : 0;
}

// ── DELETE CONFIRM ─────────────────────────────────────
function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"? This cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        const idField = document.getElementById('deleteId');
        if (form && idField) {
            idField.value = id;
            form.submit();
        }
    }
}

// ── UPDATE STATUS MODAL ────────────────────────────────
function updateStatus(id, currentStatus) {
    document.getElementById('statusId').value = id;
    const sel = document.getElementById('statusSelect');
    if (sel) sel.value = currentStatus;
    openModal('statusModal');
}

// ── AUTO-DISMISS ALERTS ────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity    = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000);
    });
});
