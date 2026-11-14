(function () {
    const zone = document.querySelector('[data-scanner-don]');
    if (!zone) {
        return;
    }
    const etat = zone.querySelector('[data-scanner-etat]');
    const bouton = zone.querySelector('[data-scanner-ouvrir]');
    const cadre = zone.querySelector('[data-scanner-cadre]');
    const video = zone.querySelector('video');
    const formulaire = document.querySelector('[data-formulaire-code-don]');
    const champCode = formulaire ? formulaire.querySelector('input[name="code_court"]') : null;
    zone.hidden = false;
    if (!('BarcodeDetector' in window) || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        bouton.hidden = true;
        etat.textContent = 'Ce navigateur ne lit pas les QR codes : ouvrez l’appareil photo du téléphone sur le QR code, ou saisissez le code à 6 caractères ci-dessous.';
        return;
    }
    let flux = null;
    let actif = false;

    const arreter = function () {
        actif = false;
        if (flux) {
            flux.getTracks().forEach(function (piste) { piste.stop(); });
            flux = null;
        }
        cadre.hidden = true;
        bouton.setAttribute('aria-expanded', 'false');
        bouton.querySelector('span').textContent = 'Scanner le QR code avec la caméra';
    };

    const traiter = function (valeur) {
        const texte = String(valeur || '').trim();
        let adresse = null;
        try {
            adresse = new URL(texte, window.location.href);
        } catch (erreur) {
            adresse = null;
        }
        if (adresse && /don_valider\.php$/.test(adresse.pathname)) {
            const code = adresse.searchParams.get('code') || '';
            if (/^[a-f0-9]{32}$/.test(code)) {
                etat.textContent = 'QR code lu : ouverture du don…';
                window.location.assign('don_valider.php?code=' + encodeURIComponent(code));
                return true;
            }
        }
        const court = texte.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (court.length === 6 && champCode) {
            champCode.value = court;
            etat.textContent = 'Code lu : vérification…';
            formulaire.submit();
            return true;
        }
        etat.textContent = 'Ce QR code n’est pas celui d’un don BIBLIOccaz. Réessayez ou saisissez le code.';
        return false;
    };

    const analyser = function (detecteur) {
        if (!actif) {
            return;
        }
        detecteur.detect(video).then(function (codes) {
            if (codes.length && codes[0].rawValue) {
                arreter();
                if (!traiter(codes[0].rawValue)) {
                    bouton.focus();
                }
                return;
            }
            requestAnimationFrame(function () { analyser(detecteur); });
        }).catch(function () {
            requestAnimationFrame(function () { analyser(detecteur); });
        });
    };

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
            etat.textContent = 'Visez le QR code affiché par le donneur.';
            return video.play();
        }).then(function () {
            analyser(new BarcodeDetector({ formats: ['qr_code'] }));
        }).catch(function () {
            arreter();
            etat.textContent = 'Caméra indisponible ou refusée : saisissez le code à 6 caractères.';
            if (champCode) {
                champCode.focus();
            }
        });
    });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden && actif) {
            arreter();
        }
    });
    window.addEventListener('pagehide', arreter);
})();
