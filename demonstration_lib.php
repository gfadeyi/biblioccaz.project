<?php
const MENTION_DEMONSTRATION = "Site de démonstration : commandes et paiements fictifs, aucun livre n'est expédié.";
const MENTION_COMMANDE_DEMONSTRATION = "BIBLIOccaz est un site de démonstration : cette commande est fictive, aucun paiement n'est débité et aucun livre ne sera expédié.";
const COOKIE_BANDEAU_DEMONSTRATION = 'bandeau_demo_ferme';

function bandeauDemonstrationVisible(): bool
{
    return ($_COOKIE[COOKIE_BANDEAU_DEMONSTRATION] ?? '') !== '1';
}
