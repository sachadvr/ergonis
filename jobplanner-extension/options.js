const DEFAULT_API_URL = 'http://localhost:8000';
const DEFAULT_FRONTEND_URL = 'http://localhost:5173';

function setStatus(message, isError = false) {
    const el = document.getElementById('status');
    el.textContent = message;
    el.style.color = isError ? '#dc2626' : '#059669';
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
        const res = await fetch(`${DEFAULT_API_URL}/api/me`, {
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
    const apiUrl = document.getElementById('apiUrl').value.trim() || DEFAULT_API_URL;
    const frontendUrl = document.getElementById('frontendUrl').value.trim() || DEFAULT_FRONTEND_URL;
    await chrome.storage.local.set({ apiUrl, frontendUrl });
    setStatus('Settings saved.');
});

document.getElementById('openFrontend').addEventListener('click', async () => {
    const frontendUrl = document.getElementById('frontendUrl').value.trim() || DEFAULT_FRONTEND_URL;
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
            // Ignore tabs that are not ready yet.
        }
    }

    setStatus('JobPlanner opened. Sign in on the frontend, then reopen settings.', true);
});

async function load() {
    if (await validateToken(await getToken())) {
        return;
    }

    const { apiUrl, frontendUrl } = await chrome.storage.local.get(['apiUrl', 'frontendUrl']);
    document.getElementById('apiUrl').value = apiUrl || DEFAULT_API_URL;
    document.getElementById('frontendUrl').value = frontendUrl || DEFAULT_FRONTEND_URL;
}
load();
