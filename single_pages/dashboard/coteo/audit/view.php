<?php
defined('C5_EXECUTE') or die('Access Denied.');


echo  Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('AUDIT'), t('SEO Audit By Coteo.')); ?>
<h1>Audit Technique SEO</h1>
<p>Réalise un audit technique SEO d'un site à partir d'une URL.</p>
<br/>
<h2>Analyse du crawl</h2>
<p>Todo : description à remplir.</p>
<hr/>
<?php

// Todo : formulaire de récupération de l'url de départ
$url = 'http://localhost/concrete5634/';

if($url) {
  // Todo : paramétrage d'une variable pour audit une page ou tout site 0 = une page / 1 = toutes pages
  $audit = new SeoAudit($url, 1);
  // Todo : Vérifier que le scan se fait bien comme si GoogleBot
}

// Analyse des entêtes

$headers = $audit->getHeaders($url);
$code = $audit->getHttpResponseCode($headers);
// Todo : fonction checkHttpResponseCode et retours à compléter
if($audit->checkHttpResponseCode($code)) {
  // Todo : voir si possibilité de reprendre le contenu de getHeaders pour éviter des requêtes
  $audit->getPageLinks($url);
  $audit->test($url);
  $audit->analysePageAll();
}
?>
<br/>
<h2>Vitesse</h2>
<p>Liste d'outils permettant d'analyser la vitesse d'un site. Todo : liste à compléter</p>
<hr/>
