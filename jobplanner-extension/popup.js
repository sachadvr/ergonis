const DEFAULT_API_URL = 'http://localhost:8000';

async function getToken() {
    const { token } = await chrome.storage.local.get('token');
    return token;
}

async function getApiUrl() {
    const { apiUrl } = await chrome.storage.local.get('apiUrl');
    return apiUrl || DEFAULT_API_URL;
}

async function loadPageData() {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (tab?.id) {
        try {
            const response = await chrome.tabs.sendMessage(tab.id, { action: 'getPageData' });
            if (response) {
                document.getElementById('title').value = response.title || '';
                document.getElementById('url').value = response.url || '';
                document.getElementById('content').value = response.content || '';
            }
        } catch {
            document.getElementById('title').value = tab?.title || '';
            document.getElementById('url').value = tab?.url || '';
        }
    }
}

function showMessage(text, isError = false) {
    const el = document.getElementById('message');
    el.textContent = text;
    el.className = isError ? 'error' : 'success';
    el.style.display = 'block';
}

document.getElementById('submit').addEventListener('click', async () => {
    const token = await getToken();
    if (!token) {
        showMessage('Configurez votre token dans les paramètres.', true);
        document.getElementById('config-msg').style.display = 'block';
        return;
    }

    const title = document.getElementById('title').value.trim();
    const url = document.getElementById('url').value.trim();
    const content = document.getElementById('content').value.trim();
    const createApplication = document.getElementById('createApp').checked;

    if (!title && !url) {
        showMessage('Titre ou URL requis.', true);
        return;
    }

    const btn = document.getElementById('submit');
    btn.disabled = true;
    showMessage('Envoi en cours...');

    try {
        const apiUrl = await getApiUrl();
        const res = await fetch(`${apiUrl}/api/job_offers/from_extension`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
            },
            body: JSON.stringify({ url, title, content, createApplication }),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.error || data.message || `Erreur ${res.status}`);
        }
        showMessage(`Offre ajoutée ! ${data.application ? 'Candidature créée.' : ''}`);
        setTimeout(() => window.close(), 1500);
    } catch (e) {
        showMessage(e.message || 'Erreur de connexion.', true);
        btn.disabled = false;
    }
});

document.getElementById('openSettings').addEventListener('click', () => {
    chrome.runtime.openOptionsPage?.() || alert('Ouvrez les paramètres de l\'extension pour configurer le token.');
});

document.getElementById('config-link')?.addEventListener('click', (e) => {
    e.preventDefault();
    chrome.runtime.openOptionsPage?.();
});

loadPageData();

const token = await getToken();
if (!token) {
    document.getElementById('config-msg').style.display = 'block';
}
