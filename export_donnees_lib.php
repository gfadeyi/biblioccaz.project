<?php

const EXPORT_DONNEES_DELAI = 60;
const EXPORT_DONNEES_LIGNES_MAX = 5000;

function requetesExportDonnees(): array
{
    $limite = ' LIMIT ' . (EXPORT_DONNEES_LIGNES_MAX + 1);
    return [
        'adresses' => ["SELECT libelle, prenom, nom, adresse, complement, code_postal, ville, telephone, par_defaut, date_creation FROM adresse_livraison WHERE id_user = ? ORDER BY date_creation" . $limite, 1],
        'commandes' => ["SELECT id_commande AS numero, date_commande, prenom, nom, adresse, sous_total, remise, frais_livraison, total, points_gagnes, points_utilises, reduction_points, statut, etape_suivi, date_annulation FROM commande WHERE id_user = ? ORDER BY date_commande" . $limite, 1],
        'lignes_de_commande' => ["SELECT cl.id_commande AS numero_commande, cl.titre, cl.etat, cl.prix_unitaire, cl.quantite FROM commande_ligne cl INNER JOIN commande c ON c.id_commande = cl.id_commande WHERE c.id_user = ? ORDER BY cl.id_commande, cl.id_ligne" . $limite, 1],
        'retours' => ["SELECT reference, id_commande AS numero_commande, type, motif, message, statut, montant, points_retires, date_demande, date_maj FROM retour_commande WHERE id_user = ? ORDER BY date_demande" . $limite, 1],
        'avis' => ["SELECT l.titre AS livre, a.note, a.titre, a.commentaire, a.statut, a.date_creation, a.date_modification FROM avis a LEFT JOIN livre l ON l.id_livre = a.id_livre WHERE a.id_user = ? ORDER BY a.date_creation" . $limite, 1],
        'favoris' => ["SELECT l.titre AS livre, l.auteur, f.date_ajout FROM favori f INNER JOIN livre l ON l.id_livre = f.id_livre WHERE f.id_user = ? ORDER BY f.date_ajout" . $limite, 1],
        'alertes_de_stock' => ["SELECT l.titre AS livre, a.date_creation, a.date_envoi FROM alerte_stock a INNER JOIN livre l ON l.id_livre = a.id_livre WHERE a.id_user = ? ORDER BY a.date_creation" . $limite, 1],
        'alertes_de_serie' => ["SELECT s.nom AS serie, a.date_creation FROM alerte_serie a INNER JOIN serie s ON s.id_serie = a.id_serie WHERE a.id_user = ? ORDER BY a.date_creation" . $limite, 1],
        'dons_proposes' => ["SELECT titre, auteur, etat, description, lieu_retrait, conditions_retrait, date_limite, statut, date_creation, date_reservation, date_recuperation FROM don WHERE id_donneur = ? ORDER BY date_creation" . $limite, 1],
        'dons_reserves' => ["SELECT titre, auteur, etat, statut, date_reservation, date_recuperation FROM don WHERE id_beneficiaire = ? ORDER BY date_reservation" . $limite, 1],
        'evenements' => ["SELECT e.titre AS evenement, e.date_debut, e.lieu, i.statut, i.date_inscription, i.present, i.date_presence FROM evenement_inscription i INNER JOIN evenement e ON e.id_evenement = i.id_evenement WHERE i.id_user = ? ORDER BY i.date_inscription" . $limite, 1],
        'annonces' => ["SELECT l.titre AS livre, x.etat, x.prix, x.statut_annonce, x.description, x.mode_remise, x.ville_remise, x.date_creation FROM exemplaire x INNER JOIN livre l ON l.id_livre = x.id_livre WHERE x.id_proprietaire = ? ORDER BY x.date_creation" . $limite, 1],
        'ventes_entre_lecteurs' => ["SELECT CASE WHEN d.id_vendeur = ? THEN 'vendeur' ELSE 'acheteur' END AS mon_role, l.titre AS livre, d.prix, d.mode_remise, d.statut, d.numero_recu, d.date_demande, d.date_acceptation, d.date_validation, d.date_confirmation, d.date_fin FROM demande_achat d INNER JOIN exemplaire x ON x.id_exemplaire = d.id_annonce INNER JOIN livre l ON l.id_livre = x.id_livre WHERE d.id_acheteur = ? OR d.id_vendeur = ? ORDER BY d.date_demande" . $limite, 3],
        'emprunts' => ["SELECT l.titre AS livre, m.statut, m.date_reservation, m.date_retrait, m.date_retour_prevue, m.date_retour, m.nb_renouvellements, m.jours_retard FROM emprunt m INNER JOIN exemplaire x ON x.id_exemplaire = m.id_exemplaire INNER JOIN livre l ON l.id_livre = x.id_livre WHERE m.id_user = ? ORDER BY m.date_reservation" . $limite, 1],
        'attente_emprunts' => ["SELECT l.titre AS livre, a.date_demande FROM emprunt_attente a INNER JOIN livre l ON l.id_livre = a.id_livre WHERE a.id_user = ? ORDER BY a.date_demande" . $limite, 1],
        'points' => ["SELECT points, type, libelle, statut, date_mouvement FROM point_mouvement WHERE id_user = ? ORDER BY date_mouvement" . $limite, 1],
        'badges' => ["SELECT code, palier, date_obtention FROM badge_obtenu WHERE id_user = ? ORDER BY date_obtention" . $limite, 1],
        'notifications' => ["SELECT type, message, lu, date_creation, date_lecture FROM notification WHERE id_user = ? ORDER BY date_creation" . $limite, 1],
        'messages_envoyes' => ["SELECT c.type_objet AS sujet, m.contenu, m.date_envoi FROM conversation_message m INNER JOIN conversation c ON c.id_conversation = m.id_conversation WHERE m.id_auteur = ? AND m.est_systeme = 0 ORDER BY m.date_envoi" . $limite, 1],
        'demandes_au_service_client' => ["SELECT reference, categorie, sujet, statut, date_creation, date_maj, date_cloture FROM demande_client WHERE id_user = ? ORDER BY date_creation" . $limite, 1],
        'messages_au_service_client' => ["SELECT d.reference, m.contenu, m.date_envoi FROM demande_client_message m INNER JOIN demande_client d ON d.id_demande = m.id_demande WHERE d.id_user = ? AND m.id_auteur = ? AND m.cote = 'client' ORDER BY m.date_envoi" . $limite, 2],
        'collections' => ["SELECT s.nom AS serie, c.date_ajout, (SELECT COUNT(*) FROM collection_volume v WHERE v.id_user = c.id_user AND v.id_serie = c.id_serie) AS volumes_possedes FROM collection_serie c INNER JOIN serie s ON s.id_serie = c.id_serie WHERE c.id_user = ? ORDER BY c.date_ajout" . $limite, 1],
        'livres_consultes' => ["SELECT l.titre AS livre, c.date_consultation FROM consultation_livre c INNER JOIN livre l ON l.id_livre = c.id_livre WHERE c.id_user = ? ORDER BY c.date_consultation" . $limite, 1],
        'categories_preferees' => ["SELECT c.nom FROM preference_categorie p INNER JOIN categorie c ON c.id_categorie = p.id_categorie WHERE p.id_user = ? ORDER BY c.nom" . $limite, 1],
        'themes_preferes' => ["SELECT t.nom FROM preference_tag p INNER JOIN tag t ON t.id_tag = p.id_tag WHERE p.id_user = ? ORDER BY t.nom" . $limite, 1],
        'fiche_auteur' => ["SELECT nom_plume, biographie, lien_site, lien_reseau, lien_editeur, date_creation, date_modification FROM fiche_auteur WHERE id_user = ?" . $limite, 1],
        'candidatures_auteur' => ["SELECT nom_plume, biographie, motivation, ouvrages, lien_site, lien_reseau, lien_editeur, statut, date_candidature, date_decision FROM candidature_auteur WHERE id_user = ? ORDER BY date_candidature" . $limite, 1],
        'candidatures_equipe' => ["SELECT role_vise, motivations, statut, date_candidature, date_decision FROM candidature_equipe WHERE id_user = ? ORDER BY date_candidature" . $limite, 1],
        'connexions' => ["SELECT espace, appareil, date_connexion, derniere_activite, date_revocation FROM session_utilisateur WHERE id_user = ? ORDER BY date_connexion DESC LIMIT 101", 1],
    ];
}

function exportDonneesPersonnelles(PDO $pdo, int $idUser): array
{
    $lecture = $pdo->prepare('SELECT pseudo, email, nom, prenom, role, statut, email_verifie, is_newsletter, date_newsletter, ville, cp, telephone, solde_points, date_inscription, historique_actif, (google_sub IS NOT NULL) AS connexion_google_liee, (photo IS NOT NULL AND photo <> \'\') AS photo_de_profil FROM user WHERE id = ?');
    $lecture->execute([$idUser]);
    $compte = $lecture->fetch(PDO::FETCH_ASSOC);
    if (!$compte) {
        throw new RuntimeException('Compte introuvable.');
    }
    $export = [
        'site' => 'BibliOccaz',
        'date_export' => date('c'),
        'information' => 'Export de vos données personnelles. Les mots de passe, jetons techniques et données des autres membres ne sont jamais inclus.',
        'compte' => $compte,
    ];
    $indisponibles = [];
    $plafonnees = [];
    foreach (requetesExportDonnees() as $section => [$sql, $nombreParametres]) {
        try {
            $requete = $pdo->prepare($sql);
            $requete->execute(array_fill(0, $nombreParametres, $idUser));
            $export[$section] = $requete->fetchAll(PDO::FETCH_ASSOC);
            $maximum = $section === 'connexions' ? 100 : EXPORT_DONNEES_LIGNES_MAX;
            if (count($export[$section]) > $maximum) {
                $export[$section] = array_slice($export[$section], 0, $maximum);
                $plafonnees[] = $section;
            }
        } catch (PDOException $erreur) {
            $export[$section] = [];
            $indisponibles[] = $section;
        }
    }
    try {
        $requete = $pdo->prepare('SELECT actif, source, date_demande, date_confirmation, date_desinscription FROM newsletter_consentement WHERE email = ?');
        $requete->execute([$compte['email']]);
        $export['consentement_newsletter'] = $requete->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $erreur) {
        $export['consentement_newsletter'] = [];
        $indisponibles[] = 'consentement_newsletter';
    }
    $export['rubriques_indisponibles'] = $indisponibles;
    $export['rubriques_plafonnees'] = $plafonnees;
    $export['limites'] = ['lignes_par_rubrique' => EXPORT_DONNEES_LIGNES_MAX, 'connexions_recentes' => 100, 'complement' => 'Si une rubrique est plafonnée ou indisponible, demandez un complément via le service client.'];
    return $export;
}

function exportDonneesAutorise(): bool
{
    return time() - (int) ($_SESSION['export_donnees_date'] ?? 0) >= EXPORT_DONNEES_DELAI;
}

function envoyerExportDonnees(PDO $pdo, int $idUser): void
{
    $json = json_encode(exportDonneesPersonnelles($pdo, $idUser), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        throw new RuntimeException('Export impossible.');
    }
    $_SESSION['export_donnees_date'] = time();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="biblioccaz-mes-donnees-' . date('Ymd') . '.json"');
    header('Content-Length: ' . strlen($json));
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex');
    echo $json;
}
