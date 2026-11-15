(function () {
    const boutons = document.querySelectorAll('[data-scanner-isbn]');
    if (!boutons.length) {
        return;
    }

    const MOTIFS = ['3211', '2221', '2122', '1411', '1132', '1231', '1114', '1312', '1213', '3112'].map(function (motif) {
        return motif.split('').map(Number);
    });
    const PARITES = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];
    const REGLAGES = [[false, 0.05], [true, 0.08], [true, 0.03]];
    const cameraPossible = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) && window.isSecureContext !== false;

    function cleEan(chiffres) {
        let somme = 0;
        for (let i = 0; i < chiffres.length; i++) {
            somme += Number(chiffres[chiffres.length - 1 - i]) * (i % 2 === 0 ? 3 : 1);
        }
        return String((10 - (somme % 10)) % 10);
    }

    function eanValide(code) {
        return /^\d{8}$|^\d{13}$/.test(code) && cleEan(code.slice(0, -1)) === code.slice(-1);
    }

    function analyserIsbn(texte) {
        const brut = String(texte || '').toUpperCase().replace(/[\s‐-―-]/g, '');
        if (/^\d{9}[\dX]$/.test(brut)) {
            let somme = 0;
            for (let i = 0; i < 10; i++) {
                somme += (brut[i] === 'X' ? 10 : Number(brut[i])) * (10 - i);
            }
            if (somme % 11 !== 0) {
                return { erreur: 'La clé de contrôle de cet ISBN-10 ne correspond pas : vérifiez les chiffres.' };
            }
            const base = '978' + brut.slice(0, 9);
            return { isbn: base + cleEan(base) };
        }
        if (/^\d{13}$/.test(brut)) {
            if (!eanValide(brut)) {
                return { erreur: 'La clé de contrôle de cet ISBN ne correspond pas : vérifiez les chiffres.' };
            }
            if (!/^97[89]/.test(brut)) {
                return { erreur: 'Ce code-barres (' + brut + ') n’est pas celui d’un livre : un ISBN commence par 978 ou 979.' };
            }
            return { isbn: brut };
        }
        if (/^\d{8}$/.test(brut)) {
            return { erreur: 'Ce code-barres court (EAN-8) n’est pas un ISBN : cherchez le code à 13 chiffres au dos du livre.' };
        }
        return { erreur: 'Saisissez les 10 ou 13 chiffres de l’ISBN, imprimés au dos du livre.' };
    }

    function chiffre(largeurs, debut, gaucheSeulement, barreDabord) {
        const e = largeurs.slice(debut, debut + 4);
        const total = e[0] + e[1] + e[2] + e[3];
        const echelle = 7 / total;
        const t1 = (e[0] + e[1]) * echelle;
        const t2 = (e[1] + e[2]) * echelle;
        const barres = (barreDabord ? e[0] + e[2] : e[1] + e[3]) * echelle;
        let meilleur = null;
        for (let d = 0; d < 10; d++) {
            const variantes = gaucheSeulement ? [['L', MOTIFS[d]]] : [['L', MOTIFS[d]], ['G', MOTIFS[d].slice().reverse()]];
            variantes.forEach(function (variante) {
                const p = variante[1];
                const ecart = Math.abs(t1 - p[0] - p[1]) + Math.abs(t2 - p[1] - p[2]) + 0.5 * Math.abs(barres - (barreDabord ? p[0] + p[2] : p[1] + p[3]));
                if (!meilleur || ecart < meilleur.ecart) {
                    meilleur = { chiffre: d, parite: variante[0], ecart: ecart, total: total };
                }
            });
        }
        return meilleur && meilleur.ecart < 1.3 ? meilleur : null;
    }

    function gardeCorrecte(largeurs, debut, nombre, module) {
        for (let k = 0; k < nombre; k++) {
            const rapport = largeurs[debut + k] / module;
            if (rapport < 0.3 || rapport > 2.2) {
                return false;
            }
        }
        return true;
    }

    function decoder(largeurs, debut, chiffresParMoitie) {
        const elements = 3 + chiffresParMoitie * 8 + 5 + 3;
        const modules = 3 + chiffresParMoitie * 14 + 5 + 3;
        if (debut < 1 || debut + elements >= largeurs.length) {
            return null;
        }
        let total = 0;
        for (let k = 0; k < elements; k++) {
            total += largeurs[debut + k];
        }
        const module = total / modules;
        const milieu = debut + 3 + chiffresParMoitie * 4;
        const fin = milieu + 5 + chiffresParMoitie * 4;
        if (largeurs[debut - 1] < module * 2.5 || largeurs[fin + 3] < module * 2.5
            || !gardeCorrecte(largeurs, debut, 3, module) || !gardeCorrecte(largeurs, milieu, 5, module) || !gardeCorrecte(largeurs, fin, 3, module)) {
            return null;
        }
        let gauche = '';
        let parites = '';
        for (let j = 0; j < chiffresParMoitie; j++) {
            const lu = chiffre(largeurs, debut + 3 + j * 4, chiffresParMoitie === 4, false);
            if (!lu || Math.abs(lu.total / module - 7) > 2) {
                return null;
            }
            gauche += lu.chiffre;
            parites += lu.parite;
        }
        let droite = '';
        for (let j = 0; j < chiffresParMoitie; j++) {
            const lu = chiffre(largeurs, milieu + 5 + j * 4, true, true);
            if (!lu || Math.abs(lu.total / module - 7) > 2) {
                return null;
            }
            droite += lu.chiffre;
        }
        let code = gauche + droite;
        if (chiffresParMoitie === 6) {
            const premier = PARITES.indexOf(parites);
            if (premier === -1) {
                return null;
            }
            code = premier + code;
        }
        return eanValide(code) ? code : null;
    }

    function largeursLigne(brutes, lissage, seuil) {
        const n = brutes.length;
        const lumieres = new Float32Array(n);
        for (let i = 0; i < n; i++) {
            lumieres[i] = lissage ? (brutes[Math.max(0, i - 1)] + brutes[i] * 2 + brutes[Math.min(n - 1, i + 1)]) / 4 : brutes[i];
        }
        let minimum = 255;
        let maximum = 0;
        const cumul = new Float64Array(n + 1);
        for (let i = 0; i < n; i++) {
            minimum = Math.min(minimum, lumieres[i]);
            maximum = Math.max(maximum, lumieres[i]);
            cumul[i + 1] = cumul[i] + lumieres[i];
        }
        if (maximum - minimum < 40) {
            return [];
        }
        const rayon = Math.max(6, Math.round(n / 24));
        const marge = (maximum - minimum) * seuil;
        const ecarts = new Float32Array(n);
        for (let i = 0; i < n; i++) {
            const a = Math.max(0, i - rayon);
            const b = Math.min(n, i + rayon + 1);
            ecarts[i] = lumieres[i] - (cumul[b] - cumul[a]) / (b - a);
        }
        const bords = [];
        let noir = null;
        for (let i = 0; i < n; i++) {
            const devientNoir = ecarts[i] < -marge;
            const devientClair = ecarts[i] > marge;
            if ((devientNoir && noir !== true) || (devientClair && noir === true)) {
                let k = i;
                while (k > 0 && (ecarts[k - 1] < 0) === devientNoir && ecarts[k - 1] !== 0) {
                    k--;
                }
                const avant = k > 0 ? ecarts[k - 1] : ecarts[k];
                const position = k > 0 && avant !== ecarts[k] ? k - 1 + avant / (avant - ecarts[k]) : k;
                bords.push(position);
                noir = devientNoir;
            }
        }
        const largeurs = [bords.length ? bords[0] : n];
        for (let i = 1; i < bords.length; i++) {
            largeurs.push(bords[i] - bords[i - 1]);
        }
        largeurs.push(bords.length ? n - bords[bords.length - 1] : 0);
        return largeurs;
    }

    function lireLigne(lumieres) {
        const sens = [lumieres, Array.prototype.slice.call(lumieres).reverse()];
        for (let s = 0; s < sens.length; s++) {
            for (let r = 0; r < REGLAGES.length; r++) {
                const largeurs = largeursLigne(sens[s], REGLAGES[r][0], REGLAGES[r][1]);
                for (let debut = 1; debut < largeurs.length; debut += 2) {
                    const code = decoder(largeurs, debut, 6) || decoder(largeurs, debut, 4);
                    if (code) {
                        return code;
                    }
                }
            }
        }
        return null;
    }

    function lireImage(contexte, largeur, hauteur) {
        const pixels = contexte.getImageData(0, 0, largeur, hauteur).data;
        const lus = {};
        const lumiere = function (x, y) {
            const i = (y * largeur + x) * 4;
            return pixels[i] * 0.299 + pixels[i + 1] * 0.587 + pixels[i + 2] * 0.114;
        };
        const essais = [];
        for (let k = 0; k < 15; k++) {
            essais.push(['ligne', Math.round(hauteur * (0.2 + 0.6 * k / 14))]);
        }
        for (let k = 0; k < 7; k++) {
            essais.push(['colonne', Math.round(largeur * (0.25 + 0.5 * k / 6))]);
        }
        for (let e = 0; e < essais.length; e++) {
            const position = essais[e][1];
            const ligne = essais[e][0] === 'ligne';
            const taille = ligne ? largeur : hauteur;
            const lumieres = new Float32Array(taille);
            for (let i = 0; i < taille; i++) {
                lumieres[i] = ligne ? lumiere(i, position) : lumiere(position, i);
            }
            const code = lireLigne(lumieres);
            if (code) {
                lus[code] = (lus[code] || 0) + 1;
                if (lus[code] >= 2) {
                    return code;
                }
            }
        }
        const codes = Object.keys(lus);
        return codes.length === 1 ? { probable: codes[0] } : null;
    }

    let dialogue = null;
    let video = null;
    let etat = null;
    let saisie = null;
    let erreurSaisie = null;
    let cadre = null;
    let boutonActif = null;
    let flux = null;
    let minuterie = null;
    let session = 0;
    let dernierRefus = '';

    function creerDialogue() {
        dialogue = document.createElement('dialog');
        dialogue.className = 'scanner-isbn';
        dialogue.setAttribute('aria-labelledby', 'scanner-isbn-titre');
        dialogue.innerHTML = '<div class="scanner-isbn__entete"><h2 id="scanner-isbn-titre">Scanner un ISBN</h2>'
            + '<button type="button" class="scanner-isbn__fermer" data-fermer aria-label="Fermer le scanner"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>'
            + '<div class="scanner-isbn__camera" hidden><video muted playsinline autoplay></video><span class="scanner-isbn__viseur" aria-hidden="true"></span></div>'
            + '<p class="scanner-isbn__etat" role="status" aria-live="polite"></p>'
            + '<form class="scanner-isbn__saisie" novalidate><label for="scanner-isbn-saisie">Saisir l’ISBN à la main</label>'
            + '<div class="scanner-isbn__ligne"><input type="text" id="scanner-isbn-saisie" inputmode="numeric" autocomplete="off" maxlength="17" placeholder="978…" aria-describedby="scanner-isbn-erreur">'
            + '<button type="submit" class="scanner-isbn__valider">Valider</button></div>'
            + '<p class="scanner-isbn__erreur" id="scanner-isbn-erreur" role="alert" hidden></p></form>';
        document.body.appendChild(dialogue);
        video = dialogue.querySelector('video');
        etat = dialogue.querySelector('.scanner-isbn__etat');
        saisie = dialogue.querySelector('input');
        erreurSaisie = dialogue.querySelector('.scanner-isbn__erreur');
        cadre = dialogue.querySelector('.scanner-isbn__camera');
        dialogue.querySelector('[data-fermer]').addEventListener('click', function () { dialogue.close(); });
        dialogue.addEventListener('close', arreterCamera);
        dialogue.addEventListener('click', function (evenement) {
            if (evenement.target === dialogue) {
                dialogue.close();
            }
        });
        dialogue.querySelector('form').addEventListener('submit', function (evenement) {
            evenement.preventDefault();
            const resultat = analyserIsbn(saisie.value);
            if (resultat.erreur) {
                erreurSaisie.textContent = resultat.erreur;
                erreurSaisie.hidden = false;
                saisie.setAttribute('aria-invalid', 'true');
                saisie.focus();
                return;
            }
            utiliser(resultat.isbn);
        });
        saisie.addEventListener('input', function () {
            erreurSaisie.hidden = true;
            saisie.removeAttribute('aria-invalid');
        });
    }

    function arreterCamera() {
        session++;
        clearTimeout(minuterie);
        if (flux) {
            flux.getTracks().forEach(function (piste) { piste.stop(); });
            flux = null;
        }
        if (video) {
            video.srcObject = null;
        }
        if (cadre) {
            cadre.hidden = true;
        }
    }

    function refuserCode(code) {
        const resultat = analyserIsbn(code);
        if (code !== dernierRefus) {
            dernierRefus = code;
            etat.textContent = resultat.erreur + ' Visez le code-barres à 13 chiffres du livre.';
        }
    }

    function boucleDetecteur(detecteur, numero) {
        if (numero !== session) {
            return;
        }
        detecteur.detect(video).then(function (codes) {
            if (numero !== session) {
                return;
            }
            for (let i = 0; i < codes.length; i++) {
                const resultat = analyserIsbn(codes[i].rawValue);
                if (resultat.isbn) {
                    utiliser(resultat.isbn);
                    return;
                }
                refuserCode(String(codes[i].rawValue || ''));
            }
            minuterie = setTimeout(function () { boucleDetecteur(detecteur, numero); }, 120);
        }).catch(function () {
            minuterie = setTimeout(function () { boucleDetecteur(detecteur, numero); }, 250);
        });
    }

    function boucleLecteur(numero) {
        const toile = document.createElement('canvas');
        const contexte = toile.getContext('2d', { willReadFrequently: true });
        let probablePrecedent = '';
        const tour = function () {
            if (numero !== session) {
                return;
            }
            if (video.videoWidth) {
                const echelle = Math.min(1, 960 / video.videoWidth);
                toile.width = Math.round(video.videoWidth * echelle);
                toile.height = Math.round(video.videoHeight * echelle);
                contexte.drawImage(video, 0, 0, toile.width, toile.height);
                let lu = lireImage(contexte, toile.width, toile.height);
                if (lu && typeof lu === 'object') {
                    const confirme = lu.probable === probablePrecedent;
                    probablePrecedent = lu.probable;
                    lu = confirme ? lu.probable : null;
                } else {
                    probablePrecedent = '';
                }
                if (typeof lu === 'string') {
                    const resultat = analyserIsbn(lu);
                    if (resultat.isbn) {
                        utiliser(resultat.isbn);
                        return;
                    }
                    refuserCode(lu);
                }
            }
            minuterie = setTimeout(tour, 140);
        };
        tour();
    }

    function choisirDetecteur() {
        if (!('BarcodeDetector' in window) || typeof BarcodeDetector.getSupportedFormats !== 'function') {
            return Promise.resolve(null);
        }
        return BarcodeDetector.getSupportedFormats().then(function (formats) {
            const voulus = ['ean_13', 'ean_8'].filter(function (format) { return formats.indexOf(format) !== -1; });
            return voulus.indexOf('ean_13') !== -1 ? new BarcodeDetector({ formats: voulus }) : null;
        }).catch(function () { return null; });
    }

    function demarrerCamera() {
        if (!cameraPossible) {
            etat.textContent = 'La caméra n’est pas disponible sur cet appareil : saisissez l’ISBN imprimé sous le code-barres.';
            saisie.focus();
            return;
        }
        const numero = ++session;
        etat.textContent = 'Ouverture de la caméra…';
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false })
            .then(function (nouveauFlux) {
                if (numero !== session) {
                    nouveauFlux.getTracks().forEach(function (piste) { piste.stop(); });
                    return null;
                }
                flux = nouveauFlux;
                video.srcObject = flux;
                cadre.hidden = false;
                return video.play().then(choisirDetecteur);
            })
            .then(function (detecteur) {
                if (numero !== session || !flux) {
                    return;
                }
                etat.textContent = 'Placez le code-barres du livre dans le cadre, bien éclairé, à une quinzaine de centimètres.';
                if (detecteur) {
                    boucleDetecteur(detecteur, numero);
                } else {
                    boucleLecteur(numero);
                }
            })
            .catch(function (erreur) {
                if (numero !== session) {
                    return;
                }
                arreterCamera();
                etat.textContent = erreur && erreur.name === 'NotAllowedError'
                    ? 'Accès à la caméra refusé : saisissez l’ISBN imprimé sous le code-barres.'
                    : 'Caméra indisponible : saisissez l’ISBN imprimé sous le code-barres.';
                saisie.focus();
            });
    }

    function infoPour(bouton) {
        const id = bouton.getAttribute('data-scanner-info');
        return id ? document.getElementById(id) : null;
    }

    function chercherCatalogue(bouton, isbn) {
        const adresse = bouton.getAttribute('data-scanner-recherche');
        if (!adresse) {
            return Promise.resolve(null);
        }
        return fetch(adresse + (adresse.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(isbn), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (reponse) { return reponse.ok ? reponse.json() : { livres: [] }; })
            .then(function (donnees) {
                const livres = Array.isArray(donnees.livres) ? donnees.livres : [];
                return livres.find(function (livre) {
                    return String(livre.isbn || '').replace(/[^0-9X]/gi, '').toUpperCase() === isbn;
                }) || false;
            })
            .catch(function () { return null; });
    }

    function annoncer(bouton, texte, livre) {
        const info = infoPour(bouton);
        if (!info) {
            return;
        }
        info.replaceChildren(document.createTextNode(texte));
        const lien = bouton.getAttribute('data-scanner-lien');
        if (livre && lien) {
            const a = document.createElement('a');
            a.href = lien + encodeURIComponent(livre.id);
            a.textContent = livre.titre + (livre.auteur ? ' — ' + livre.auteur : '');
            info.append(a);
        } else if (livre) {
            info.append(document.createTextNode(livre.titre + (livre.auteur ? ' — ' + livre.auteur : '')));
        }
    }

    function signaler(champ) {
        champ.dispatchEvent(new Event('input', { bubbles: true }));
        champ.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function utiliser(isbn) {
        const bouton = boutonActif;
        arreterCamera();
        dialogue.close();
        if (navigator.vibrate) {
            navigator.vibrate(60);
        }
        const cible = document.getElementById(bouton.getAttribute('data-scanner-cible'));
        if (!cible) {
            return;
        }
        const mode = bouton.getAttribute('data-scanner-mode') || 'champ';
        cible.focus();
        if (mode === 'recherche') {
            cible.value = isbn;
            if (cible.form) {
                cible.form.submit();
            }
            return;
        }
        if (mode === 'catalogue') {
            annoncer(bouton, 'ISBN ' + isbn + ' lu, recherche dans le catalogue…');
            chercherCatalogue(bouton, isbn).then(function (livre) {
                if (livre) {
                    cible.value = livre.titre;
                    signaler(cible);
                    const champId = document.getElementById(bouton.getAttribute('data-scanner-id') || '');
                    if (champId) {
                        champId.value = String(livre.id);
                    }
                    annoncer(bouton, '✓ ISBN ' + isbn + ' : ', livre);
                } else {
                    annoncer(bouton, livre === false
                        ? 'ISBN ' + isbn + ' absent du catalogue : saisissez le titre du livre, l’équipe créera sa fiche.'
                        : 'ISBN ' + isbn + ' lu, mais le catalogue ne répond pas : saisissez le titre du livre.');
                }
                cible.focus();
            });
            return;
        }
        cible.dataset.isbnScanne = isbn;
        cible.value = isbn;
        signaler(cible);
        if (mode === 'champ') {
            annoncer(bouton, 'ISBN ' + isbn + ' lu.');
            chercherCatalogue(bouton, isbn).then(function (livre) {
                if (livre) {
                    annoncer(bouton, 'Attention, cet ISBN est déjà au catalogue : ', livre);
                } else if (livre === false) {
                    annoncer(bouton, '✓ ISBN ' + isbn + ' lu : aucun livre du catalogue ne l’utilise encore.');
                }
            });
        }
    }

    boutons.forEach(function (bouton) {
        bouton.hidden = false;
        const zone = bouton.closest('form');
        if (zone) {
            zone.classList.add('scanner-isbn-actif');
        }
        bouton.addEventListener('click', function () {
            if (!dialogue) {
                creerDialogue();
            }
            boutonActif = bouton;
            dernierRefus = '';
            saisie.value = '';
            erreurSaisie.hidden = true;
            saisie.removeAttribute('aria-invalid');
            etat.textContent = '';
            dialogue.showModal();
            demarrerCamera();
        });
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden && dialogue && dialogue.open) {
            arreterCamera();
            etat.textContent = 'Caméra mise en pause : saisissez l’ISBN ou rouvrez le scanner.';
        }
    });
    window.addEventListener('pagehide', arreterCamera);
    window.BibliOccazIsbn = { analyser: analyserIsbn, lireLigne: lireLigne };
})();
