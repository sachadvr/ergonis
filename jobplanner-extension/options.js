document.getElementById('save').addEventListener('click', async () => {
    const apiUrl = document.getElementById('apiUrl').value.trim() || 'http://localhost:8000';
    const token = document.getElementById('token').value.trim();
    await chrome.storage.local.set({ apiUrl, token });
    document.getElementById('status').textContent = 'Paramètres enregistrés.';
});

async function load() {
    const { apiUrl, token } = await chrome.storage.local.get(['apiUrl', 'token']);
    document.getElementById('apiUrl').value = apiUrl || 'http://localhost:8000';
    document.getElementById('token').value = token || '';
}
load();
