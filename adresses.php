<?php
require_once 'config.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];

function revenir(string $type, string $message): void
{
    $_SESSION['flash_compte'] = [$type, $message];
    header("Location: adresses.php");
    exit();
}

function adresseDeLUtilisateur(PDO $pdo, int $idAdresse, int $idUser): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM adresse_livraison WHERE id_adresse = ? AND id_user = ?");
    $stmt->execute([$idAdresse, $idUser]);
    return $stmt->fetch() ?: null;
}

$erreur = '';
$valeurs = [];
$enEdition = isset($_GET['modifier']) ? adresseDeLUtilisateur($pdo, (int) $_GET['modifier'], $idUser) : null;
if ($enEdition) {
    $valeurs = $enEdition;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        revenir('danger', "Session expirée, merci de réessayer.");
    }
    $action = $_POST['action'] ?? '';
    $idAdresse = (int) ($_POST['id_adresse'] ?? 0);

    if ($action === 'supprimer' || $action === 'defaut') {
        $adresse = adresseDeLUtilisateur($pdo, $idAdresse, $idUser);
        if (!$adresse) {
            revenir('warning', "Adresse introuvable.");
        }
        if ($action === 'supprimer') {
            $pdo->prepare("DELETE FROM adresse_livraison WHERE id_adresse = ?")->execute([$idAdresse]);
            $restantes = adressesDe($pdo, $idUser);
            if ($adresse['par_defaut'] && $restantes) {
                $pdo->prepare("UPDATE adresse_livraison SET par_defaut = 1 WHERE id_adresse = ?")->execute([$restantes[0]['id_adresse']]);
            }
            revenir('success', "Adresse « {$adresse['libelle']} » supprimée.");
        }
        $pdo->prepare("UPDATE adresse_livraison SET par_defaut = (id_adresse = ?) WHERE id_user = ?")->execute([$idAdresse, $idUser]);
        revenir('success', "« {$adresse['libelle']} » est maintenant votre adresse par défaut.");
    }

    if ($action === 'enregistrer') {
        foreach (['libelle', 'prenom', 'nom', 'adresse', 'complement', 'code_postal', 'ville', 'telephone'] as $c) {
            $valeurs[$c] = trim($_POST[$c] ?? '');
        }
        $enEdition = $idAdresse ? adresseDeLUtilisateur($pdo, $idAdresse, $idUser) : null;
        $nbAdresses = count(adressesDe($pdo, $idUser));

        if ($valeurs['libelle'] === '' || $valeurs['prenom'] === '' || $valeurs['nom'] === '' || $valeurs['adresse'] === '' || $valeurs['ville'] === '') {
            $erreur = "Merci de remplir tous les champs obligatoires.";
        } elseif (!preg_match('/^\d{5}$/', $valeurs['code_postal'])) {
            $erreur = "Le code postal doit contenir 5 chiffres.";
        } elseif ($valeurs['telephone'] !== '' && !preg_match('/^[0-9 +().-]{6,20}$/', $valeurs['telephone'])) {
            $erreur = "Le numéro de téléphone n'est pas valide.";
        } elseif (!$enEdition && $idAdresse) {
            $erreur = "Adresse introuvable.";
        } elseif (!$enEdition && $nbAdresses >= ADRESSES_MAX) {
            $erreur = "Vous avez atteint le maximum de " . ADRESSES_MAX . " adresses. Supprimez-en une pour en ajouter une nouvelle.";
        } else {
            $donnees = [
                mb_substr($valeurs['libelle'], 0, 50), $valeurs['prenom'], $valeurs['nom'], $valeurs['adresse'],
                $valeurs['complement'] ?: null, $valeurs['code_postal'], $valeurs['ville'], $valeurs['telephone'] ?: null,
            ];
            if ($enEdition) {
                $donnees[] = $idAdresse;
                $pdo->prepare("UPDATE adresse_livraison SET libelle = ?, prenom = ?, nom = ?, adresse = ?, complement = ?, code_postal = ?, ville = ?, telephone = ? WHERE id_adresse = ?")
                    ->execute($donnees);
                revenir('success', "Adresse mise à jour.");
            }
            $donnees[] = $idUser;
            $donnees[] = $nbAdresses === 0 ? 1 : 0;
            $pdo->prepare("INSERT INTO adresse_livraison (libelle, prenom, nom, adresse, complement, code_postal, ville, telephone, id_user, par_defaut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute($donnees);
            revenir('success', "Adresse ajoutée." . ($nbAdresses === 0 ? " Elle sera proposée par défaut lors de vos commandes." : ''));
        }
    }
}

if (!$valeurs) {
    $stmt = $pdo->prepare("SELECT prenom, nom FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    $valeurs = ($stmt->fetch() ?: []) + ['libelle' => 'Maison'];
}

$adresses = adressesDe($pdo, $idUser);
$csrf = jetonCsrf();
$flash = $_SESSION['flash_compte'] ?? null;
unset($_SESSION['flash_compte']);
$v = fn(string $c) => htmlspecialchars((string) ($valeurs[$c] ?? ''));

include 'header.php';
?>

<div class="container my-4" style="max-width: 1000px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0" style="color:#274e13;"><i class="bi bi-geo-alt"></i> Mes adresses</h1>
        <a href="profil.php" class="btn btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left"></i> Mon compte</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <?php if (!$adresses): ?>
                <div class="card border-0 shadow-sm p-4 text-center text-muted" style="border-radius: 15px;">
                    <i class="bi bi-house fs-1 mb-2"></i>
                    Aucune adresse enregistrée. Ajoutez-en une pour commander plus vite.
                </div>
            <?php endif; ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($adresses as $a): ?>
                    <div class="card border-0 shadow-sm <?= $a['par_defaut'] ? 'border border-success' : '' ?>" style="border-radius: 15px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($a['libelle']) ?>
                                        <?php if ($a['par_defaut']): ?><span class="badge bg-success rounded-pill ms-1">Par défaut</span><?php endif; ?>
                                    </div>
                                    <div class="small mt-1">
                                        <?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?><br>
                                        <?= htmlspecialchars($a['adresse']) ?><br>
                                        <?php if ($a['complement']): ?><?= htmlspecialchars($a['complement']) ?><br><?php endif; ?>
                                        <?= htmlspecialchars($a['code_postal'] . ' ' . $a['ville']) ?>
                                        <?php if ($a['telephone']): ?><br><i class="bi bi-telephone"></i> <?= htmlspecialchars($a['telephone']) ?><?php endif; ?>
                                    </div>
                                </div>
                                <a href="adresses.php?modifier=<?= $a['id_adresse'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill" title="Modifier"><i class="bi bi-pencil"></i></a>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <?php if (!$a['par_defaut']): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="defaut">
                                        <input type="hidden" name="id_adresse" value="<?= $a['id_adresse'] ?>">
                                        <button class="btn btn-sm btn-link text-success p-0">Utiliser par défaut</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="ms-auto" data-confirmer="Supprimer cette adresse de votre carnet ?" data-bouton-confirmer="Supprimer">
                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id_adresse" value="<?= $a['id_adresse'] ?>">
                                    <button class="btn btn-sm btn-link text-danger p-0">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <form method="POST" class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h2 class="h5 fw-bold mb-3"><?= $enEdition ? 'Modifier « ' . htmlspecialchars($enEdition['libelle']) . ' »' : 'Nouvelle adresse' ?></h2>
                <?php if ($erreur): ?>
                    <div class="alert alert-danger small py-2"><?= htmlspecialchars($erreur) ?></div>
                <?php endif; ?>
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="enregistrer">
                <input type="hidden" name="id_adresse" value="<?= $enEdition['id_adresse'] ?? 0 ?>">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small fw-bold" for="libelle">Nom de l'adresse *</label>
                        <input type="text" id="libelle" name="libelle" class="form-control" maxlength="50" value="<?= $v('libelle') ?>" placeholder="Maison, Travail, Chez mes parents..." required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold" for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" maxlength="100" value="<?= $v('prenom') ?>" required autocomplete="given-name">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold" for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" class="form-control" maxlength="100" value="<?= $v('nom') ?>" required autocomplete="family-name">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold" for="adresse">Adresse *</label>
                        <input type="text" id="adresse" name="adresse" class="form-control" maxlength="255" value="<?= $v('adresse') ?>" placeholder="N° et nom de rue" required autocomplete="address-line1">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold" for="complement">Complément</label>
                        <input type="text" id="complement" name="complement" class="form-control" maxlength="255" value="<?= $v('complement') ?>" placeholder="Bâtiment, étage, digicode..." autocomplete="address-line2">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold" for="code_postal">Code postal *</label>
                        <input type="text" id="code_postal" name="code_postal" class="form-control" maxlength="5" inputmode="numeric" value="<?= $v('code_postal') ?>" required autocomplete="postal-code">
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold" for="ville">Ville *</label>
                        <input type="text" id="ville" name="ville" class="form-control" maxlength="100" value="<?= $v('ville') ?>" required autocomplete="address-level2">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold" for="telephone">Téléphone (pour le livreur)</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" maxlength="20" value="<?= $v('telephone') ?>" autocomplete="tel">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-success rounded-pill px-4 fw-bold"><?= $enEdition ? 'Enregistrer' : 'Ajouter l\'adresse' ?></button>
                    <?php if ($enEdition): ?><a href="adresses.php" class="btn btn-link text-muted">Annuler</a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
