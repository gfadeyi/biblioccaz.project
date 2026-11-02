<?php
const ROLES_CANDIDATURE_EQUIPE = ['moderateur' => 'Modérateur', 'gestionnaire' => 'Gestionnaire'];
const MISSIONS_CANDIDATURE_EQUIPE = [
    'moderateur' => 'Valider les fiches de livres, les avis, les annonces et les dons, et veiller au respect des échanges entre lecteurs.',
    'gestionnaire' => 'Suivre le stock et les commandes, préparer les emprunts au comptoir et organiser les événements.',
];
const ROLES_CANDIDATS_EQUIPE = ['client', 'auteur'];
const ROLES_DEJA_EQUIPE = ['admin', 'gestionnaire', 'moderateur'];
const MOTIVATIONS_EQUIPE_MIN = 30;
const MOTIVATIONS_EQUIPE_MAX = 5000;
const CANDIDATURES_EQUIPE_PAR_IP_JOUR = 3;
const DELAI_CANDIDATURE_EQUIPE_JOURS = 30;
const MESSAGE_DECISION_EQUIPE_MAX = 1000;

function candidaturesEquipeDisponibles(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM candidature_equipe LIMIT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function nombreCandidaturesEquipeEnAttente(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM candidature_equipe WHERE statut = 'en_attente'")->fetchColumn();
    } catch (PDOException $e) {
        return (int) $pdo->query("SELECT COUNT(*) FROM user WHERE statut = 'en_attente_moderateur'")->fetchColumn();
    }
}

function pseudoEquipeValide(string $pseudo): bool
{
    return (bool) preg_match('/\A[\p{L}\p{N}._-]{3,50}\z/u', $pseudo);
}

function pseudoEquipeLibre(PDO $pdo, string $pseudo, ?int $idUserAutorise = null): bool
{
    $stmt = $pdo->prepare('SELECT id FROM user WHERE pseudo = ?');
    $stmt->execute([$pseudo]);
    $idExistant = $stmt->fetchColumn();
    return $idExistant === false || (int) $idExistant === $idUserAutorise;
}

function empreinteIpCandidatureEquipe(): string
{
    return hash('sha256', 'candidature-equipe|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function derniereCandidatureEquipe(PDO $pdo, ?int $idUser, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM candidature_equipe WHERE id_user = ? OR email = ? ORDER BY date_candidature DESC, id_candidature DESC LIMIT 1');
    $stmt->execute([$idUser, $email]);
    return $stmt->fetch() ?: null;
}

function etatCandidatureEquipe(PDO $pdo, ?array $membre, string $email = ''): array
{
    if ($membre) {
        if (in_array($membre['role'], ROLES_DEJA_EQUIPE, true)) {
            return ['etat' => 'equipe', 'candidature' => null, 'date_possible' => null];
        }
        if (!in_array($membre['role'], ROLES_CANDIDATS_EQUIPE, true) || $membre['statut'] !== 'actif') {
            return ['etat' => 'interdite', 'candidature' => null, 'date_possible' => null];
        }
        $email = $membre['email'];
    }
    $candidature = derniereCandidatureEquipe($pdo, $membre ? (int) $membre['id'] : null, $email);
    if ($candidature && $candidature['statut'] === 'en_attente') {
        return ['etat' => 'en_attente', 'candidature' => $candidature, 'date_possible' => null];
    }
    if ($candidature && $candidature['statut'] === 'refusee' && $candidature['date_decision']) {
        $datePossible = date('Y-m-d H:i:s', strtotime($candidature['date_decision'] . ' +' . DELAI_CANDIDATURE_EQUIPE_JOURS . ' days'));
        if ($datePossible > date('Y-m-d H:i:s')) {
            return ['etat' => 'refusee_recente', 'candidature' => $candidature, 'date_possible' => $datePossible];
        }
    }
    return ['etat' => 'ouverte', 'candidature' => $candidature, 'date_possible' => null];
}

function saisieCandidatureEquipe(array $source, ?array $membre): array
{
    $texte = fn(string $champ) => is_string($source[$champ] ?? null) ? trim(str_replace("\r\n", "\n", $source[$champ])) : '';
    $valeurs = [
        'role_vise' => $texte('role_vise'),
        'prenom' => $texte('prenom'),
        'nom' => $texte('nom'),
        'pseudo_souhaite' => $texte('pseudo_souhaite'),
        'email' => $membre ? $membre['email'] : mb_strtolower($texte('email')),
        'motivations' => $texte('motivations'),
    ];
    $erreurs = [];
    if (!array_key_exists($valeurs['role_vise'], ROLES_CANDIDATURE_EQUIPE)) {
        $erreurs[] = 'Choisissez le rôle qui vous intéresse : modérateur ou gestionnaire.';
    }
    if ($valeurs['prenom'] === '' || $valeurs['nom'] === '') {
        $erreurs[] = 'Indiquez votre prénom et votre nom.';
    } elseif (mb_strlen($valeurs['prenom']) > 100 || mb_strlen($valeurs['nom']) > 100) {
        $erreurs[] = 'Le prénom et le nom ne doivent pas dépasser 100 caractères.';
    }
    if (!pseudoEquipeValide($valeurs['pseudo_souhaite'])) {
        $erreurs[] = 'Le pseudo souhaité doit contenir de 3 à 50 caractères : lettres, chiffres, point, tiret ou tiret bas.';
    }
    if (!$membre && (mb_strlen($valeurs['email']) > 150 || !filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL))) {
        $erreurs[] = "L'adresse email n'est pas valide.";
    }
    $longueurMotivations = mb_strlen($valeurs['motivations']);
    if ($longueurMotivations < MOTIVATIONS_EQUIPE_MIN) {
        $erreurs[] = 'Présentez-vous en quelques phrases (au moins ' . MOTIVATIONS_EQUIPE_MIN . ' caractères).';
    } elseif ($longueurMotivations > MOTIVATIONS_EQUIPE_MAX) {
        $erreurs[] = 'Vos motivations ne doivent pas dépasser ' . MOTIVATIONS_EQUIPE_MAX . ' caractères.';
    }
    return ['valeurs' => $valeurs, 'erreurs' => $erreurs];
}

function deposerCandidatureEquipe(PDO $pdo, array $valeurs, ?array $membre): int
{
    $etat = etatCandidatureEquipe($pdo, $membre, $valeurs['email']);
    if ($etat['etat'] === 'en_attente') {
        throw new DomainException("Une candidature est déjà en cours d'étude pour cette adresse. L'équipe vous répondra par mail.");
    }
    if ($etat['etat'] === 'refusee_recente') {
        throw new DomainException('Une nouvelle candidature sera possible à partir du ' . date('d/m/Y', strtotime($etat['date_possible'])) . '.');
    }
    if ($etat['etat'] !== 'ouverte') {
        throw new DomainException('Votre compte ne permet pas de postuler.');
    }
    if (!$membre) {
        $stmt = $pdo->prepare('SELECT 1 FROM user WHERE email = ?');
        $stmt->execute([$valeurs['email']]);
        if ($stmt->fetchColumn()) {
            throw new DomainException('Un compte existe déjà avec cette adresse : connectez-vous pour postuler depuis votre compte.');
        }
    }
    $empreinte = empreinteIpCandidatureEquipe();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM candidature_equipe WHERE empreinte_ip = ? AND date_candidature > NOW() - INTERVAL 1 DAY');
    $stmt->execute([$empreinte]);
    if ((int) $stmt->fetchColumn() >= CANDIDATURES_EQUIPE_PAR_IP_JOUR) {
        throw new DomainException('Trop de candidatures ont été envoyées depuis cette connexion aujourd’hui. Réessayez demain.');
    }
    $pdo->prepare('INSERT INTO candidature_equipe (role_vise, nom, prenom, pseudo_souhaite, email, motivations, id_user, empreinte_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$valeurs['role_vise'], $valeurs['nom'], $valeurs['prenom'], $valeurs['pseudo_souhaite'], $valeurs['email'], $valeurs['motivations'], $membre ? (int) $membre['id'] : null, $empreinte]);
    return (int) $pdo->lastInsertId();
}

function candidaturesEquipeEnAttente(PDO $pdo): array
{
    $candidatures = $pdo->query("
        SELECT c.*, u.pseudo AS pseudo_compte, u.role AS role_compte, u.statut AS statut_compte, u.email_verifie
        FROM candidature_equipe c
        LEFT JOIN user u ON u.id = c.id_user
        WHERE c.statut = 'en_attente'
        ORDER BY c.date_candidature, c.id_candidature")->fetchAll();
    foreach ($candidatures as &$candidature) {
        $idCompte = $candidature['id_user'] !== null ? (int) $candidature['id_user'] : null;
        $candidature['pseudo_libre'] = pseudoEquipeLibre($pdo, $candidature['pseudo_souhaite'], $idCompte);
        $candidature['email_pris'] = false;
        if ($idCompte === null) {
            $stmt = $pdo->prepare('SELECT 1 FROM user WHERE email = ?');
            $stmt->execute([$candidature['email']]);
            $candidature['email_pris'] = (bool) $stmt->fetchColumn();
        }
    }
    unset($candidature);
    return $candidatures;
}

function historiqueCandidaturesEquipe(PDO $pdo, int $limite = 30): array
{
    $stmt = $pdo->prepare("
        SELECT c.*, d.pseudo AS pseudo_decideur
        FROM candidature_equipe c
        LEFT JOIN user d ON d.id = c.id_decideur
        WHERE c.statut <> 'en_attente'
        ORDER BY c.date_decision DESC, c.id_candidature DESC
        LIMIT ?");
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function candidatureEquipeAVerrouiller(PDO $pdo, int $idCandidature): array
{
    $stmt = $pdo->prepare('SELECT * FROM candidature_equipe WHERE id_candidature = ? FOR UPDATE');
    $stmt->execute([$idCandidature]);
    $candidature = $stmt->fetch();
    if (!$candidature || $candidature['statut'] !== 'en_attente') {
        throw new DomainException('Cette candidature a déjà été traitée.');
    }
    return $candidature;
}

function clotureAncienneCandidatureModerateur(PDO $pdo, int $idUser, string $statut, int $idDecideur, ?string $message): void
{
    try {
        $pdo->prepare("UPDATE candidature_moderateur SET statut = ?, date_decision = NOW(), id_decideur = ?, message_decision = ? WHERE id_user = ? AND statut = 'en_attente'")
            ->execute([$statut, $idDecideur, $message, $idUser]);
    } catch (PDOException $e) {
    }
}

function accepterCandidatureEquipe(PDO $pdo, int $idCandidature, string $pseudo, int $idDecideur, string $message): array
{
    $message = mb_substr(trim($message), 0, MESSAGE_DECISION_EQUIPE_MAX);
    if (!pseudoEquipeValide($pseudo)) {
        throw new DomainException('Le pseudo doit contenir de 3 à 50 caractères : lettres, chiffres, point, tiret ou tiret bas.');
    }
    $pdo->beginTransaction();
    try {
        $candidature = candidatureEquipeAVerrouiller($pdo, $idCandidature);
        if (!array_key_exists($candidature['role_vise'], ROLES_CANDIDATURE_EQUIPE)) {
            throw new DomainException('Le rôle demandé est inconnu.');
        }
        if ($candidature['id_user'] !== null) {
            $stmt = $pdo->prepare('SELECT id, pseudo, email, nom, prenom, role, statut, email_verifie FROM user WHERE id = ? FOR UPDATE');
            $stmt->execute([(int) $candidature['id_user']]);
            $compte = $stmt->fetch();
            if (!$compte) {
                throw new DomainException("Le compte de ce membre n'existe plus : refusez cette candidature.");
            }
            if (in_array($compte['role'], ROLES_DEJA_EQUIPE, true)) {
                throw new DomainException('Ce membre fait déjà partie de l’équipe.');
            }
            if (in_array($compte['statut'], ['refuse_temporaire', 'refuse_definitif'], true)) {
                throw new DomainException('Ce compte est suspendu : réactivez-le avant d’accepter la candidature, ou refusez-la.');
            }
            if (!pseudoEquipeLibre($pdo, $pseudo, (int) $compte['id'])) {
                throw new DomainException("Le pseudo « $pseudo » est déjà pris : choisissez-en un autre.");
            }
            $pdo->prepare("UPDATE user SET role = ?, pseudo = ?, statut = 'actif' WHERE id = ?")
                ->execute([$candidature['role_vise'], $pseudo, (int) $compte['id']]);
            $mode = (int) $compte['email_verifie'] === 1 ? 'promotion' : 'activation';
            $compte = ['pseudo' => $pseudo, 'role' => $candidature['role_vise']] + $compte;
        } else {
            $stmt = $pdo->prepare('SELECT 1 FROM user WHERE email = ?');
            $stmt->execute([$candidature['email']]);
            if ($stmt->fetchColumn()) {
                throw new DomainException('Un compte existe déjà avec l’adresse ' . $candidature['email'] . ' : demandez à la personne de postuler depuis son compte, ou refusez cette candidature.');
            }
            if (!pseudoEquipeLibre($pdo, $pseudo)) {
                throw new DomainException("Le pseudo « $pseudo » est déjà pris : choisissez-en un autre.");
            }
            try {
                $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, est_verifie) VALUES (?, ?, ?, ?, ?, ?, 'actif', 0, 0)")
                    ->execute([$pseudo, $candidature['email'], password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $candidature['nom'], $candidature['prenom'], $candidature['role_vise']]);
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }
                throw new DomainException('Ce pseudo ou cette adresse email vient d’être utilisé par un autre compte.');
            }
            $compte = [
                'id' => (int) $pdo->lastInsertId(),
                'pseudo' => $pseudo,
                'email' => $candidature['email'],
                'nom' => $candidature['nom'],
                'prenom' => $candidature['prenom'],
                'role' => $candidature['role_vise'],
            ];
            $mode = 'creation';
        }
        $pdo->prepare("UPDATE candidature_equipe SET statut = 'acceptee', date_decision = NOW(), id_decideur = ?, pseudo_attribue = ?, message_decision = ?, id_user = ? WHERE id_candidature = ?")
            ->execute([$idDecideur, $pseudo, $message !== '' ? $message : null, (int) $compte['id'], $idCandidature]);
        clotureAncienneCandidatureModerateur($pdo, (int) $compte['id'], 'acceptee', $idDecideur, $message !== '' ? $message : null);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return ['candidature' => $candidature, 'compte' => $compte, 'mode' => $mode, 'message' => $message];
}

function refuserCandidatureEquipe(PDO $pdo, int $idCandidature, int $idDecideur, string $message): array
{
    $message = mb_substr(trim($message), 0, MESSAGE_DECISION_EQUIPE_MAX);
    $pdo->beginTransaction();
    try {
        $candidature = candidatureEquipeAVerrouiller($pdo, $idCandidature);
        $pdo->prepare("UPDATE candidature_equipe SET statut = 'refusee', date_decision = NOW(), id_decideur = ?, message_decision = ? WHERE id_candidature = ?")
            ->execute([$idDecideur, $message !== '' ? $message : null, $idCandidature]);
        if ($candidature['id_user'] !== null) {
            $pdo->prepare("UPDATE user SET role = 'client', statut = 'actif' WHERE id = ? AND statut = 'en_attente_moderateur'")
                ->execute([(int) $candidature['id_user']]);
            clotureAncienneCandidatureModerateur($pdo, (int) $candidature['id_user'], 'refusee', $idDecideur, $message !== '' ? $message : null);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return ['candidature' => $candidature, 'message' => $message];
}

function prevenirDecisionCandidatureEquipe(PDO $pdo, array $resultat, bool $acceptee): bool
{
    require_once __DIR__ . '/recuperation_mdp_lib.php';
    require_once __DIR__ . '/notifications_lib.php';
    $candidature = $resultat['candidature'];
    $libelleRole = mb_strtolower(ROLES_CANDIDATURE_EQUIPE[$candidature['role_vise']] ?? $candidature['role_vise']);
    $complement = $resultat['message'] !== '' ? "\n\nMessage de l'équipe :\n" . $resultat['message'] : '';
    if (!$acceptee) {
        return notifierParMail($candidature['email'], 'Votre candidature à l’équipe BIBLIOccaz',
            "Bonjour {$candidature['prenom']},\n\nMerci pour l'intérêt que vous portez à BIBLIOccaz. Après étude, l'équipe n'a pas retenu votre candidature au rôle de $libelleRole pour le moment. Vous pourrez en déposer une nouvelle dans " . DELAI_CANDIDATURE_EQUIPE_JOURS . " jours." . $complement,
            SITE_URL, 'Revenir sur BIBLIOccaz');
    }
    $compte = $resultat['compte'];
    if ($resultat['mode'] === 'promotion') {
        return notifierParMail($compte['email'], 'Votre candidature est acceptée',
            "Bonjour {$compte['prenom']},\n\nBonne nouvelle : l'équipe BIBLIOccaz a accepté votre candidature. Votre compte a maintenant le rôle de $libelleRole, avec le pseudo {$compte['pseudo']}.\n\nConnectez-vous au back-office avec vos identifiants habituels." . $complement,
            SITE_URL . '/admin/login.php', 'Accéder au back-office');
    }
    $presentation = $resultat['mode'] === 'creation'
        ? "Bonne nouvelle : l'équipe BIBLIOccaz a accepté votre candidature et vient de vous créer un compte (rôle : $libelleRole), avec le pseudo {$compte['pseudo']}."
        : "Bonne nouvelle : l'équipe BIBLIOccaz a accepté votre candidature. Votre compte a maintenant le rôle de $libelleRole, avec le pseudo {$compte['pseudo']}.";
    return envoyerInvitationCompte($pdo, $compte, 'Votre candidature est acceptée', $presentation, $complement);
}
