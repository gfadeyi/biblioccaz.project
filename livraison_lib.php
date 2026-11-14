<?php

const COMMANDE_LIVRAISON_JOURS = 3;

function dateLivraisonCommande(array $commande): string
{
    return date('Y-m-d H:i:s', strtotime($commande['date_commande'] . ' +' . COMMANDE_LIVRAISON_JOURS . ' days'));
}

function commandeLivree(array $commande): bool
{
    return ($commande['statut'] ?? 'validee') === 'validee' && dateLivraisonCommande($commande) <= date('Y-m-d H:i:s');
}
