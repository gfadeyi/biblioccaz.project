<?php
require_once __DIR__ . '/sessions_lib.php';

function tableauSessions(PDO $pdo, string $recherche = '', string $role = '', bool $multiples = false, int $page = 1): array
{
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $pdo->beginTransaction();
    try {
        $instant = $pdo->query('SELECT NOW()')->fetchColumn();
        $base = ' FROM session_utilisateur s INNER JOIN user u ON u.id = s.id_user WHERE ' . conditionSessionValide();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(n),0) AS sessions, COUNT(*) AS utilisateurs, COALESCE(SUM(n > 1),0) AS multiples FROM (SELECT COUNT(*) AS n' . $base . ' GROUP BY u.id) actifs');
        $stmt->execute([$instant]); $indicateurs = array_map('intval', $stmt->fetch());
        $stmt = $pdo->prepare("SELECT COUNT(*) AS sessions, COUNT(DISTINCT u.id) AS utilisateurs" . $base . " AND u.role IN ('client','auteur')");
        $stmt->execute([$instant]); $cibles = array_map('intval', $stmt->fetch());
        $params = [$instant];
        if ($recherche !== '') {
            $base .= " AND (u.pseudo LIKE ? ESCAPE '!' OR u.nom LIKE ? ESCAPE '!' OR u.prenom LIKE ? ESCAPE '!' OR u.email LIKE ? ESCAPE '!')";
            $motif = '%' . strtr(mb_substr($recherche,0,100), ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            array_push($params, $motif, $motif, $motif, $motif);
        }
        if (in_array($role, ['client','auteur','admin','moderateur','gestionnaire'], true)) { $base .= ' AND u.role = ?'; $params[] = $role; }
        $groupe = $base . ' GROUP BY u.id,u.pseudo,u.nom,u.prenom,u.email,u.role' . ($multiples ? ' HAVING COUNT(*) > 1' : '');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM (SELECT u.id' . $groupe . ') filtres'); $stmt->execute($params); $total = (int) $stmt->fetchColumn();
        $pages = max(1,(int) ceil($total/20)); $page = max(1,min($page,$pages));
        $stmt = $pdo->prepare('SELECT u.id, u.pseudo, u.nom, u.prenom, u.email, u.role, COUNT(*) AS nombre' . $groupe . ' ORDER BY u.pseudo, u.id LIMIT 20 OFFSET ' . (($page-1)*20));
        $stmt->execute($params); $membres = $stmt->fetchAll();
        $sessions = [];
        if ($membres) {
            $ids = array_column($membres,'id');
            $stmt = $pdo->prepare('SELECT s.id_suivi,s.id_user,s.espace,s.date_connexion,s.derniere_activite,(s.empreinte = ?) AS courante' . $base . ' AND u.id IN (' . implode(',', array_fill(0,count($ids),'?')) . ') ORDER BY s.derniere_activite DESC,s.id_suivi DESC');
            $stmt->execute(array_merge([empreinteSessionCourante()],$params,$ids));
            foreach ($stmt->fetchAll() as $session) { $sessions[$session['id_user']][] = $session; }
        }
        $pdo->commit();
        return compact('instant','indicateurs','cibles','membres','sessions','total','pages','page');
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

function revoquerSessionsClientsAuteurs(PDO $pdo, int $idAdministrateur, int $comptesAttendus, int $sessionsAttendues): array
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT id,role,statut,email_verifie FROM user WHERE id = ? OR role IN ('client','auteur') ORDER BY id FOR UPDATE");
        $stmt->execute([$idAdministrateur]); $membres = $stmt->fetchAll(); $ids = []; $autorise = false;
        foreach ($membres as $membre) {
            if ((int) $membre['id'] === $idAdministrateur) { $autorise = $membre['role'] === 'admin' && $membre['statut'] === 'actif' && (int) $membre['email_verifie'] === 1; }
            elseif (in_array($membre['role'],['client','auteur'],true)) { $ids[] = (int) $membre['id']; }
        }
        if (!$autorise) { throw new DomainException('Action réservée aux administrateurs actifs.'); }
        $instant = $pdo->query('SELECT NOW()')->fetchColumn(); $cibles = [];
        if ($ids) {
            $stmt = $pdo->prepare("SELECT s.id_suivi,s.id_user FROM session_utilisateur s INNER JOIN user u ON u.id=s.id_user WHERE " . conditionSessionValide() . " AND u.role IN ('client','auteur') AND u.id IN (" . implode(',',array_fill(0,count($ids),'?')) . ') ORDER BY s.id_suivi FOR UPDATE');
            $stmt->execute(array_merge([$instant],$ids)); $cibles = $stmt->fetchAll();
        }
        $comptes = count(array_unique(array_column($cibles,'id_user')));
        if ($comptes !== $comptesAttendus || count($cibles) !== $sessionsAttendues) { throw new DomainException('Le nombre de connexions concernées a changé. Vérifiez le nouvel aperçu et confirmez de nouveau.'); }
        $stmt = $pdo->prepare("UPDATE session_utilisateur s INNER JOIN user u ON u.id=s.id_user SET s.date_revocation=NOW(),s.id_revoque_par=? WHERE s.id_suivi=? AND u.role IN ('client','auteur') AND " . conditionSessionValide());
        $fermees = 0; $comptesFermes = [];
        foreach ($cibles as $cible) { $stmt->execute([$idAdministrateur,$cible['id_suivi'],$instant]); if ($stmt->rowCount()) { $fermees++; $comptesFermes[$cible['id_user']] = true; } }
        $pdo->commit(); return ['sessions' => $fermees,'utilisateurs' => count($comptesFermes)];
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}
