(function () {
    const zone = document.querySelector('[data-scanner-qr]');
    if (!zone || !('BarcodeDetector' in window) || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return;
    }
    const formulaire = zone.closest('form');
    const champ = formulaire.querySelector('input[name="code"]');
    const bouton = zone.querySelector('[data-scanner-ouvrir]');
    const cadre = zone.querySelector('[data-scanner-cadre]');
    const video = zone.querySelector('video');
    const etat = zone.querySelector('[data-scanner-etat]');
    let flux = null;
    let actif = false;

    function arreter() {
        actif = false;
        if (flux) {
            flux.getTracks().forEach(function (piste) { piste.stop(); });
            flux = null;
        }
        cadre.hidden = true;
        bouton.setAttribute('aria-expanded', 'false');
        bouton.querySelector('span').textContent = 'Scanner le QR code';
    }

    function analyser(detecteur) {
        if (!actif) {
            return;
        }
        detecteur.detect(video).then(function (codes) {
            if (codes.length && codes[0].rawValue) {
                champ.value = codes[0].rawValue;
                etat.textContent = 'QR code lu, recherche de l’emprunt…';
                arreter();
                formulaire.submit();
                return;
            }
            requestAnimationFrame(function () { analyser(detecteur); });
        }).catch(function () {
            requestAnimationFrame(function () { analyser(detecteur); });
        });
    }

    zone.hidden = false;
    bouton.addEventListener('click', function () {
        if (actif) {
            arreter();
            etat.textContent = 'Caméra arrêtée.';
            return;
        }
        BarcodeDetector.getSupportedFormats().then(function (formats) {
            if (formats.indexOf('qr_code') === -1) {
                throw new Error('format');
            }
            return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        }).then(function (nouveauFlux) {
            flux = nouveauFlux;
            video.srcObject = flux;
            cadre.hidden = false;
            actif = true;
            bouton.setAttribute('aria-expanded', 'true');
            bouton.querySelector('span').textContent = 'Arrêter la caméra';
            etat.textContent = 'Présentez le QR code du lecteur devant la caméra.';
            return video.play();
        }).then(function () {
            analyser(new BarcodeDetector({ formats: ['qr_code'] }));
        }).catch(function () {
            arreter();
            etat.textContent = 'Caméra indisponible : saisissez le code à 6 caractères.';
            champ.focus();
        });
    });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden && actif) {
            arreter();
        }
    });
})();
