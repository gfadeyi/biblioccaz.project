<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/parametres_techniques_lib.php';
$texte = texteLegalPersonnalise('texte_cgu');
if ($texte === '') { http_response_code(404); }
$titreDocument = 'Conditions générales d’utilisation';
include __DIR__ . '/header.php';
?>
<div class="container my-4 page-legale">
    <h1 class="titre-page-compte">Conditions générales d’utilisation</h1>
    <section class="carte-compte" style="overflow-wrap:anywhere;"><?= $texte !== '' ? nl2br(htmlspecialchars($texte, ENT_QUOTES, 'UTF-8')) : 'Ce document n’est pas encore publié.' ?></section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
