<?php
defined('C5_EXECUTE') or die('Access Denied.');


echo  Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('SEO'), t('SEO Tools By Coteo.')); ?>
<h1>Gérer les balises Meta Title et Meta Description</h1>
<h2>Export</h2>
<p>Export des informations au format csv pour exploitation dans un tableur.</p>
<?php
//liste les pages à exporter
Loader::model('page_list');
$pl = new PageList();
$pages = $pl->get();

//détermine le chemin vers le fichier temporaire
$fh = Loader::helper('file');
$temp_path = $fh->getTemporaryDirectory();

$file_name = 'coteo-seo-export.csv';
$file_url = $temp_path . '/' . $file_name;
$fp = fopen($file_url, 'w');

foreach ($pages as $page) {
  echo $page->getCollectionID() . ',' . $page->getCollectionName() . ',' . $page->getCollectionDescription() . '<br/>';
  $fields = array($page->getCollectionID(), $page->getCollectionName(), $page->getCollectionDescription());
  fputcsv($fp, $fields);
}
// Importe le fichier dans le Gestionnaire de fichiers
Loader::library("file/importer");
$fi = new FileImporter();
$f = $fi->import($file_url, $file_name);

fclose($fp);

// si pb avec Excel, voir
// https://www.concrete5.org/developers/bugs/5-7-5-3/form-results-export-csv-encoding-problem
// http://php.net/manual/fr/function.fputcsv.php
 ?>
 <a href ="<?php echo File::getRelativePathFromID($f->getFileID()); ?>">Télécharger</a>
 <h2>Import</h2>
 <p>Import des informations au format csv et mise à jour.</p>
