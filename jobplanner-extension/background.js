const DEFAULT_API_URL = 'https://api.ergonis.app/';

async function getStoredToken() {
    const { token } = await chrome.storage.local.get('token');
    return token;
}

async function getApiUrl() {
    const { apiUrl } = await chrome.storage.local.get('apiUrl');
    return apiUrl || DEFAULT_API_URL;
}

chrome.runtime.onMessage.addListener((request, _sender, sendResponse) => {
    if (request.action === 'getToken') {
        getStoredToken().then(sendResponse);
        return true;
    }
    if (request.action === 'getApiUrl') {
        getApiUrl().then(sendResponse);
        return true;
    }
    return false;
});
