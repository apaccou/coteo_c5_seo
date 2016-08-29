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
$url = 'http://www.tourisme-gravelines.fr';

if($url) {
  // Todo : paramétrage d'une variable pour audit une page ou tout site 0 = une page / 1 = toutes pages
  $audit = new SeoAudit($url, 1);

  if(!$audit->checkIsUrl($url)) {
    echo 'Problème : veuillez fournir une url valide';
    exit;
  }
  if(!$audit->checkIsAccesible($url)) {
    echo 'Problème : le site n\'est pas accessible';
    exit;
  }
Loader::library('timing_helper', 'coteo_c5_seo');
$th = new TimingHelper();
 $audit->analysePageAll();
$time = $th->time();
// $th->start();
//   $audit->crawlAnalyseAll($url);
//   echo '<br/>';
// echo $th->time();
$audit->reportAll();


  // Todo : Vérifier que le scan se fait bien comme si GoogleBot
  echo '<ul>';
  echo '<li>Site analysé : ' . $audit->startingHttpRequest->_url . '</li>';
  echo '<li>URL de départ : ' . $audit->startingHttpRequest->_url . '</li>';
  echo '<li>Temps d\'analyse : ' . $time .'</li>';
  echo '</ul>';
}

// Analyse des entêtes

// $headers = $audit->getHeaders($url);
// $code = $audit->getHttpResponseCode($headers);
// // Todo : fonction checkHttpResponseCode et retours à compléter
// if($audit->checkHttpResponseCode($code)) {
//   // Todo : voir si possibilité de reprendre le contenu de getHeaders pour éviter des requêtes
//   //$audit->getPageLinks($url);
//   //$audit->testDOMDocument($url);
//   $audit->analysePageAll();
// }
?>
<br/>
<h2>Vitesse</h2>
<p>Liste d'outils permettant d'analyser la vitesse d'un site. Todo : liste à compléter</p>
<hr/>
