<?php

const SPOILER_SEUIL = 4;
const SPOILER_SOURCES = ['auteur' => 'signalé par son auteur', 'detecte' => 'détecté automatiquement'];
const SPOILER_NEGATIONS = [
    '/\b(sans|pas de|aucun|no|zero)\s+(spoil\w*|divulgach\w*)/u',
    '/\bsans (rien |trop en )?(devoiler|reveler|dire)\b/u',
];
const SPOILER_INDICES = [
    '/\bspoil(s|e|es|er|ers|eur|eurs)?\b/u' => 4,
    '/\bdivulgach\w*/u' => 4,
    '/\b(le|la|l\')\s?(coupable|meurtrier|meurtriere|assassin|tueur|tueuse|traitre|traitresse)\s+(est|etait|c\'est|n\'est autre)\b/u' => 4,
    '/\b(meurt|meurent|mourra|se suicide|se fait tuer|est tue|est tuee|sont tues|disparait)\b[^.!?]{0,40}\b(a la fin|dans le dernier chapitre|a la derniere page)\b/u' => 4,
    '/\b(a la fin|dans le dernier chapitre|a la derniere page)\b[^.!?]{0,60}\b(meurt|meurent|tue|tuee|mort|morte|se marient|epouse|trahit|se revele|on apprend|on decouvre)\b/u' => 4,
    '/\b(meurt|meurent|decede|decedent|se suicide|se fait tuer|est tue|est tuee|assassine|assassinee)\b/u' => 2,
    '/\b(plot twist|twist|retournement|coup de theatre)\b/u' => 2,
    '/\b(dernier chapitre|derniers chapitres|epilogue|denouement|fin du (livre|roman|tome|manga|recit))\b/u' => 2,
    '/\bon (decouvre|apprend|comprend) (que|qu\'|enfin)/u' => 2,
    '/\b(il|elle) s\'avere (que|etre)\b/u' => 2,
    '/\b(se revele etre|revele que|revele qu\'|sa vraie identite|identite secrete|vraie identite)\b/u' => 2,
    '/\b(trahit|trahissent|finit par (mourir|tuer|epouser|trahir))\b/u' => 2,
    '/\ben fait,? (c\'est|il|elle)\b/u' => 1,
    '/\b(a la fin|finalement|la fin|en realite|revelation|surprise finale)\b/u' => 1,
];

function texteNormaliseSpoiler(string $texte): string
{
    $texte = mb_strtolower(str_replace(['’', '‘', '`'], "'", $texte), 'UTF-8');
    $texte = strtr($texte, ['à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'œ' => 'oe', 'æ' => 'ae']);
    return preg_replace('/\s+/u', ' ', $texte);
}

function scoreSpoilerAvis(string $titre, string $commentaire): int
{
    $texte = texteNormaliseSpoiler($titre . '. ' . $commentaire);
    $texte = preg_replace(SPOILER_NEGATIONS, ' ', $texte);
    $score = 0;
    foreach (SPOILER_INDICES as $motif => $poids) {
        if (preg_match($motif, $texte)) { $score += $poids; }
    }
    return min($score, 99);
}

function marqueSpoilerAvis(bool $choixAuteur, int $score): ?string
{
    if ($choixAuteur) { return 'auteur'; }
    return $score >= SPOILER_SEUIL ? 'detecte' : null;
}

function spoilerAvisDisponible(PDO $pdo): bool
{
    static $disponible = null;
    if ($disponible === null) {
        try {
            $disponible = (bool) $pdo->query("SHOW COLUMNS FROM avis LIKE 'spoiler'")->fetch();
        } catch (PDOException $erreur) {
            $disponible = false;
        }
    }
    return $disponible;
}

function conditionSansSpoiler(PDO $pdo, string $alias = 'a'): string
{
    if (!preg_match('/\A[a-z_]+\z/', $alias)) { throw new InvalidArgumentException('Alias invalide'); }
    return spoilerAvisDisponible($pdo) ? " AND $alias.spoiler IS NULL" : '';
}

function libelleSpoiler(?string $marque): string
{
    return SPOILER_SOURCES[$marque ?? ''] ?? '';
}
