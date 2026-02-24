const DEFAULT_API_URL = 'http://localhost:8000';
const DEFAULT_FRONTEND_URL = 'http://localhost:5173';

async function getToken() {
    const { token } = await chrome.storage.local.get('token');
    return token;
}

async function setToken(token) {
    if (token) {
        await chrome.storage.local.set({ token });
    } else {
        await chrome.storage.local.remove('token');
    }
}

async function getApiUrl() {
    const { apiUrl } = await chrome.storage.local.get('apiUrl');
    return apiUrl || DEFAULT_API_URL;
}

async function getFrontendUrl() {
    const { frontendUrl } = await chrome.storage.local.get('frontendUrl');
    return frontendUrl || DEFAULT_FRONTEND_URL;
}

function toTabPattern(url) {
    try {
        const parsed = new URL(url);
        return `${parsed.origin}/*`;
    } catch {
        return `${DEFAULT_FRONTEND_URL}/*`;
    }
}

async function syncTokenFromFrontendTab() {
    const frontendUrl = await getFrontendUrl();
    const tabs = await chrome.tabs.query({ url: toTabPattern(frontendUrl) });

    for (const tab of tabs) {
        if (!tab.id) {
            continue;
        }

        try {
            const response = await chrome.tabs.sendMessage(tab.id, { action: 'getAuthToken' });
            const token = response?.token?.trim?.() || '';
            if (token) {
                await chrome.storage.local.set({ token });
                return token;
            }
        } catch {
            // Ignore tabs that do not have the content script yet.
        }
    }

    return '';
}

async function validateToken(token) {
    if (!token) {
        return false;
    }

    try {
        const apiUrl = await getApiUrl();
        const res = await fetch(`${apiUrl}/api/me`, {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        });

        if (res.ok) {
            return true;
        }

        if (res.status === 401 || res.status === 403) {
            await setToken('');
        }
    } catch {
        // Network failure: keep the token as-is and let submit handle it.
        return true;
    }

    return false;
}

function showAuthMessage(show) {
    document.getElementById('auth-msg').style.display = show ? 'block' : 'none';
    document.getElementById('form').style.display = show ? 'none' : 'block';
}

async function openFrontend() {
    const frontendUrl = await getFrontendUrl();
    const tab = await chrome.tabs.create({ url: frontendUrl });
    await waitForTabComplete(tab.id);
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
        showAuthMessage(true);
        return;
    }

    const title = document.getElementById('title').value.trim();
    const url = document.getElementById('url').value.trim();
    const content = document.getElementById('content').value.trim();

    if (!title && !url) {
        showMessage('Title or URL required.', true);
        return;
    }

    const btn = document.getElementById('submit');
    btn.disabled = true;
    showMessage('Sending...');

    try {
        const apiUrl = await getApiUrl();
        const res = await fetch(`${apiUrl}/api/job_offers/from_extension`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
            },
            body: JSON.stringify({ url, title, content, createApplication: true }),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            if (res.status === 401 || res.status === 403) {
                await setToken('');
                showAuthMessage(true);
            }
            throw new Error(data.error || data.message || `Error ${res.status}`);
        }
        showMessage(`Offer added! ${data.application ? 'Application created.' : ''}`);
        setTimeout(() => window.close(), 1500);
    } catch (e) {
        showMessage(e.message || 'Connection error.', true);
        btn.disabled = false;
    }
});

document.getElementById('openFrontend')?.addEventListener('click', async () => {
    await openFrontend();
    const token = await syncTokenFromFrontendTab();
    if (token) {
        showMessage('Token synced from the frontend.');
        return;
    }

    showMessage('JobPlanner opened. Sign in on the frontend, then reopen the popup.', true);
});

document.getElementById('openSettings').addEventListener('click', () => {
    chrome.runtime.openOptionsPage?.() || alert('Open the extension settings to configure the token.');
});

document.getElementById('syncLogin').addEventListener('click', async () => {
    await openFrontend();
    const token = await syncTokenFromFrontendTab();
    if (token) {
        showMessage('Token synced from the frontend.');
        return;
    }

    showMessage('Career Atelier opened. Sign in, then click Sync login again.', true);
});

document.getElementById('resetToken').addEventListener('click', async () => {
    await setToken('');
    showMessage('Token reset.', true);
    showAuthMessage(true);
});

document.getElementById('config-link')?.addEventListener('click', (e) => {
    e.preventDefault();
    openFrontend();
});

loadPageData();

async function init() {
    const token = await getToken();
    if (await validateToken(token)) {
        showAuthMessage(false);
        return;
    }

    const syncedToken = await syncTokenFromFrontendTab();
    if (await validateToken(syncedToken)) {
        showMessage('Token synced from the frontend.');
        return;
    }

    showAuthMessage(true);
}

init();
