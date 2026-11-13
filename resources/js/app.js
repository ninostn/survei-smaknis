//
const themeToggle = document.querySelector('[data-admin-theme-toggle]');

const publishDialog = document.getElementById('publish-dialog');

if (publishDialog && typeof publishDialog.showModal === 'function') {
    const body = publishDialog.querySelector('[data-publish-body]');
    const closeButton = publishDialog.querySelector('[data-publish-close]');

    function displayPublishPage(html) {
        const page = new DOMParser().parseFromString(html, 'text/html');
        const content = page.querySelector('[data-publish-content]');
        if (!content) throw new Error('Formulir publikasi tidak tersedia.');
        body.replaceChildren(content);

        const form = body.querySelector('form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                });
                if (response.redirected && !new URL(response.url).pathname.endsWith('/publish')) {
                    window.location.assign(response.url);
                    return;
                }
                if (!response.ok) throw new Error('Publikasi gagal diproses.');
                displayPublishPage(await response.text());
            } catch {
                window.location.assign(form.action);
            } finally {
                button.disabled = false;
            }
        });
    }

    document.querySelectorAll('[data-publish-open]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            body.textContent = 'Memuat formulir...';
            publishDialog.showModal();
            try {
                const response = await fetch(link.href);
                if (!response.ok || response.redirected) {
                    window.location.assign(response.url || link.href);
                    return;
                }
                displayPublishPage(await response.text());
            } catch {
                window.location.assign(link.href);
            }
        });
    });

    closeButton.addEventListener('click', () => publishDialog.close());
}

const createDialog = document.getElementById('admin-create-dialog');
const createLink = document.querySelector('[data-create-dialog]');

if (createDialog && createLink && typeof createDialog.showModal === 'function') {
    createLink.addEventListener('click', (event) => {
        event.preventDefault();
        createDialog.showModal();
    });
    createDialog.querySelector('[data-create-close]').addEventListener('click', () => createDialog.close());
    if (createDialog.dataset.reopen === 'true') createDialog.showModal();
}

if (themeToggle) {
    const root = document.documentElement;
    const syncToggle = () => {
        themeToggle.setAttribute('aria-pressed', String(root.dataset.adminTheme === 'dark'));
    };

    syncToggle();

    themeToggle.addEventListener('click', () => {
        const theme = root.dataset.adminTheme === 'dark' ? 'light' : 'dark';
        root.dataset.adminTheme = theme;
        syncToggle();

        try {
            localStorage.setItem('smaknis-admin-theme', theme);
        } catch {
            // The current page still supports theme switching when storage is unavailable.
        }
    });
}

document.querySelectorAll('form[method="GET"] select').forEach((select) => {
    if (select.hidden) return;

    const wrapper = select.parentElement;
    const menu = document.createElement('details');
    menu.className = 'relative w-full';

    const summary = document.createElement('summary');
    summary.className = 'flex h-10 cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm font-medium text-slate-800 transition-colors hover:border-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500';
    const label = document.createElement('span');
    const icon = document.createElement('span');
    icon.className = 'text-slate-400';
    icon.textContent = '⌄';
    summary.append(label, icon);

    const options = document.createElement('div');
    options.className = 'absolute left-0 top-full z-30 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg';

    const sync = () => {
        label.textContent = select.selectedOptions[0]?.textContent.trim() || 'Pilih';
        options.querySelectorAll('button').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.value === select.value));
        });
    };

    Array.from(select.options).forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600 focus-visible:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-500';
        button.textContent = option.textContent.trim();
        button.dataset.value = option.value;
        button.addEventListener('click', () => {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
            menu.open = false;
            summary.focus();
        });
        options.append(button);
    });

    menu.append(summary, options);
    select.hidden = true;
    wrapper.classList.add('relative');
    wrapper.querySelectorAll('.material-symbols-outlined').forEach((element) => {
        element.hidden = true;
    });
    wrapper.append(menu);
    sync();

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) menu.open = false;
    });
});
