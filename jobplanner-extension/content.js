(function () {
    function getPageData() {
        const title = document.title || '';
        const url = window.location.href || '';
        let content = '';

        const selection = window.getSelection();
        if (selection && selection.toString().trim()) {
            content = selection.toString().trim();
        } else {
            const body = document.body;
            if (body) {
                const text = body.innerText || body.textContent || '';
                content = text.substring(0, 5000).trim();
            }
        }

        return { title, url, content };
    }

    chrome.runtime.onMessage.addListener((request, _sender, sendResponse) => {
        if (request.action === 'getPageData') {
            sendResponse(getPageData());
            return true;
        }

        if (request.action === 'getAuthToken') {
            sendResponse({ token: localStorage.getItem('auth_token') || '' });
            return true;
        }

        return false;
    });
})();
