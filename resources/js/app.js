import './bootstrap';
import Alpine from 'alpinejs';
import SignaturePad from 'signature_pad';

window.Alpine = Alpine;
window.SignaturePad = SignaturePad;

// ===== Theme Manager =====
const ThemeManager = {
    init() {
        const saved = localStorage.getItem('wrkplan-theme') || 'light';
        const color = localStorage.getItem('wrkplan-color') || null;
        this.apply(saved, color);
    },
    apply(theme, color = null) {
        document.documentElement.setAttribute('data-theme', theme);
        if (color && theme === 'custom') {
            document.documentElement.style.setProperty('--user-color', color);
            // Darken color for sidebar
            document.documentElement.style.setProperty('--user-color-dark', this.darken(color, 0.3));
        }
        localStorage.setItem('wrkplan-theme', theme);
        if (color) localStorage.setItem('wrkplan-color', color);
    },
    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        this.apply(current === 'dark' ? 'light' : 'dark');
    },
    darken(hex, amount) {
        let r = parseInt(hex.slice(1,3),16);
        let g = parseInt(hex.slice(3,5),16);
        let b = parseInt(hex.slice(5,7),16);
        r = Math.max(0, Math.floor(r * (1 - amount)));
        g = Math.max(0, Math.floor(g * (1 - amount)));
        b = Math.max(0, Math.floor(b * (1 - amount)));
        return `#${r.toString(16).padStart(2,'0')}${g.toString(16).padStart(2,'0')}${b.toString(16).padStart(2,'0')}`;
    }
};
ThemeManager.init();
window.ThemeManager = ThemeManager;

// ===== Sidebar Toggle =====
const SidebarManager = {
    toggle() {
        document.querySelector('.sidebar')?.classList.toggle('open');
    }
};
window.SidebarManager = SidebarManager;

// ===== Toast Notifications =====
const Toast = {
    show(message, type = 'info', duration = 4000) {
        const toast = document.createElement('div');
        const colors = {
            success: 'bg-emerald-500',
            error: 'bg-red-500',
            warning: 'bg-amber-500',
            info: 'bg-indigo-500'
        };
        toast.className = `fixed top-4 right-4 z-[9999] ${colors[type] || colors.info} text-white px-6 py-4 rounded-xl shadow-2xl transform translate-x-full transition-transform duration-300 flex items-center gap-3 max-w-sm`;
        toast.innerHTML = `
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>${message}</span>`;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.remove('translate-x-full'));
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
};
window.Toast = Toast;

// ===== WrkPlan API Client =====
const WrkPlanAPI = {
    async request(method, endpoint, data = null, options = {}) {
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                ...options.headers
            }
        };
        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }
        const response = await fetch(`/api/v1${endpoint}`, config);
        const json = await response.json();
        if (!response.ok) throw { status: response.status, data: json };
        return json;
    },
    get: (endpoint, options) => WrkPlanAPI.request('GET', endpoint, null, options),
    post: (endpoint, data, options) => WrkPlanAPI.request('POST', endpoint, data, options),
    put: (endpoint, data, options) => WrkPlanAPI.request('PUT', endpoint, data, options),
    patch: (endpoint, data, options) => WrkPlanAPI.request('PATCH', endpoint, data, options),
    delete: (endpoint, options) => WrkPlanAPI.request('DELETE', endpoint, null, options),
};
window.WrkPlanAPI = WrkPlanAPI;

// ===== Alpine Stores =====
Alpine.store('sidebar', { open: window.innerWidth > 768 });
Alpine.store('theme', {
    current: localStorage.getItem('wrkplan-theme') || 'light',
    toggle() {
        this.current = this.current === 'dark' ? 'light' : 'dark';
        ThemeManager.apply(this.current);
    }
});

Alpine.start();
