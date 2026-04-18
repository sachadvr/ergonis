const DEFAULT_MODE = 'prod';
const DEFAULT_API_URL = 'https://api.ergonis.app/';
const DEFAULT_FRONTEND_URL = 'https://ergonis.app/';
const LOCAL_API_URL = 'http://localhost:8000';
const LOCAL_FRONTEND_URL = 'http://localhost:5173';

function setStatus(message, isError = false) {
    const el = document.getElementById('status');
    el.textContent = message;
    el.style.color = isError ? '#dc2626' : '#059669';
}

function getDefaultsForMode(mode) {
    return mode === 'local'
        ? { apiUrl: LOCAL_API_URL, frontendUrl: LOCAL_FRONTEND_URL }
        : { apiUrl: DEFAULT_API_URL, frontendUrl: DEFAULT_FRONTEND_URL };
}

function setModeLabel(mode) {
    document.getElementById('isLocalMode').checked = mode === 'local';
}

function setFieldVisibility(mode) {
    document.getElementById('apiUrlField').classList.toggle('field-hidden', mode !== 'local');
    document.getElementById('frontendUrlField').classList.toggle('field-hidden', mode !== 'local');
}

function setUrlsForMode(mode) {
    const defaults = getDefaultsForMode(mode);
    document.getElementById('apiUrl').value = defaults.apiUrl;
    document.getElementById('frontendUrl').value = defaults.frontendUrl;
}

function toTabPattern(url) {
    try {
        const parsed = new URL(url);
        return `${parsed.origin}/*`;
    } catch {
        return `${DEFAULT_FRONTEND_URL}/*`;
    }
}

async function getToken() {
    const { token } = await chrome.storage.local.get('token');
    return token;
}

async function validateToken(token) {
    if (!token) {
        return false;
    }

    try {
        const { mode = DEFAULT_MODE, apiUrl } = await chrome.storage.local.get(['mode', 'apiUrl']);
        const res = await fetch(`${apiUrl || getDefaultsForMode(mode).apiUrl}/api/me`, {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        });

        return res.ok;
    } catch {
        return false;
    }
}

function waitForTabComplete(tabId, timeoutMs = 15000) {
    if (!tabId) {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        const timer = setTimeout(() => {
            chrome.tabs.onUpdated.removeListener(listener);
            resolve();
        }, timeoutMs);

        const listener = (updatedTabId, changeInfo) => {
            if (updatedTabId !== tabId || changeInfo.status !== 'complete') {
                return;
            }

            clearTimeout(timer);
            chrome.tabs.onUpdated.removeListener(listener);
            resolve();
        };

        chrome.tabs.onUpdated.addListener(listener);
    });
}

document.getElementById('save').addEventListener('click', async () => {
    const mode = document.getElementById('isLocalMode').checked ? 'local' : 'prod';
    const defaults = getDefaultsForMode(mode);
    const apiUrl = document.getElementById('apiUrl').value.trim() || defaults.apiUrl;
    const frontendUrl = document.getElementById('frontendUrl').value.trim() || defaults.frontendUrl;
    await chrome.storage.local.set({ mode, apiUrl, frontendUrl });
    setStatus('Settings saved.');
});

document.getElementById('isLocalMode').addEventListener('change', (event) => {
    const mode = event.target.checked ? 'local' : 'prod';
    setFieldVisibility(mode);
    setUrlsForMode(mode);
});

document.getElementById('openFrontend').addEventListener('click', async () => {
    const mode = document.getElementById('isLocalMode').checked ? 'local' : 'prod';
    const frontendUrl = document.getElementById('frontendUrl').value.trim() || getDefaultsForMode(mode).frontendUrl;
    const tab = await chrome.tabs.create({ url: frontendUrl });
    await waitForTabComplete(tab.id);
    const tabs = await chrome.tabs.query({ url: toTabPattern(frontendUrl) });
    for (const tab of tabs) {
        if (!tab.id) continue;

        try {
            const response = await chrome.tabs.sendMessage(tab.id, { action: 'getAuthToken' });
            const token = response?.token?.trim?.() || '';
            if (token) {
                await chrome.storage.local.set({ token });
                setStatus('Token synced from the frontend.');
                return;
            }
        } catch {
        }
    }

    setStatus('JobPlanner opened. Sign in on the frontend, then reopen settings.', true);
});

async function load() {
    const { mode = DEFAULT_MODE, apiUrl, frontendUrl } = await chrome.storage.local.get([
        'mode',
        'apiUrl',
        'frontendUrl',
    ]);

    setModeLabel(mode);
    setFieldVisibility(mode);
    if (mode === 'local') {
        document.getElementById('apiUrl').value = apiUrl || getDefaultsForMode(mode).apiUrl;
        document.getElementById('frontendUrl').value = frontendUrl || getDefaultsForMode(mode).frontendUrl;
    }

    await validateToken(await getToken());
}
load();
