// Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').then(registration => {
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
        }, error => {
            console.log('ServiceWorker registration failed: ', error);
        });
    });
}

// Install Prompt Script
window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    window.deferredPrompt = event;
    showInstallPromotion();
});

window.addEventListener('appinstalled', event => {
    console.log('INSTALL: Success');
});

function showInstallPromotion() {
    const installButton = document.getElementById('install-button');
    if (installButton) {
        installButton.style.display = 'block';
        installButton.addEventListener('click', () => {
            const promptEvent = window.deferredPrompt;
            if (promptEvent) {
                promptEvent.prompt();
                promptEvent.userChoice.then(choiceResult => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    window.deferredPrompt = null;
                });
            }
        });
    }
}
