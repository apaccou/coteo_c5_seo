<?php
defined('C5_EXECUTE') or die('Access Denied.');


echo  Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('SEO'), t('SEO Tools By Coteo.')); ?>
<h1>Gérer les balises Meta Title et Meta Description</h1>
<hr/>
<h2>Export</h2>
<p>Export des informations au format csv pour exploitation dans un tableur.</p>
<?php

//création du fichier XSD
$XSD = $this->controller->fileExportXSD();


if ($XSD) {
    $fh = Loader::helper('file');
    //détermine le chemin vers la racine du package
    $packagePath = Package::getByID($this->c->pkgID)->getPackagePath();

    $fileExportName = DashboardCoteoSeoController::FILE_XSD_NAME;
    $fileExportUrl = $packagePath . '/' . $fileExportName;
    $fp = fopen($fileExportUrl, 'w');
    //add BOM to fix UTF-8 in Excel
    fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    fputs($fp, $XSD);
    fclose($fp);
}

//création du fichier XML
$XML = $this->controller->fileExportXML();

if ($XML) {
  $fh = Loader::helper('file');
  //détermine le chemin vers le fichier temporaire
  $tempPath = sys_get_temp_dir();

  $fileExportName = 'coteo-seo-export.xml';
  $fileExportUrl = $tempPath . '/' . $fileExportName;
  $fp = fopen($fileExportUrl, 'w');
  //add BOM to fix UTF-8 in Excel
  fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
  fputs($fp, $XML);
  fclose($fp);

  $form = Loader::helper('form');
?>
<form method="post" action="<?php echo $this->action('fileDownload'); ?>">
  <p>Le fichier XML a été généré.</p>
  <?php echo $form->hidden("fileUrl", $fileExportUrl); ?>
  <input type="submit" name="submit" value="Télécharger" />
</form>
<?php
}

//liste les pages à exporter
Loader::model('page_list');
$pl = new PageList();
$pages = $pl->get();

//détermine le chemin vers le fichier temporaire
$fh = Loader::helper('file');
$tempPath = $fh->getTemporaryDirectory();

$fileExportName = 'coteo-seo-export.csv';
$fileExportUrl = $tempPath . '/' . $fileExportName;
$fp = fopen($fileExportUrl, 'w');
//add BOM to fix UTF-8 in Excel
fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
// entêtes de colonne
fputcsv($fp, array('Page ID', 'Nom de page', 'Titre', 'Description', 'Keywords', 'URL'));

$nh = Loader::helper('navigation');

foreach ($pages as $cobj) {
  $pageName = $cobj->getCollectionName();
  $pageName = htmlspecialchars($pageName, ENT_COMPAT, APP_CHARSET);

  $pageTitle = $cobj->getCollectionName();
  $pageTitle = htmlspecialchars($pageTitle, ENT_COMPAT, APP_CHARSET);
  $autoTitle = sprintf(PAGE_TITLE_FORMAT, SITE, $pageTitle);
  $pageTitle = $cobj->getAttribute('meta_title') ? $cobj->getAttribute('meta_title') : $autoTitle;

  $pageDescription = $cobj->getCollectionDescription();
  $autoDesc = htmlspecialchars($pageDescription, ENT_COMPAT, APP_CHARSET);
  $pageDescription = $cobj->getAttribute('meta_description') ? $cobj->getAttribute('meta_description') : $autoDesc;
  // Nettoyage pour compatibilité sous Excel des retours à la ligne et retours chariots
  $pageDescription = str_replace("\n","",$pageDescription);
  $pageDescription = str_replace("\r","",$pageDescription);

  $pageKeywords = $cobj->getAttribute('meta_keywords');

  $pageURL = $nh->getCollectionURL($cobj);

  echo '<p>' . $cobj->getCollectionID() . ',' . $pageName . ',' . $pageTitle . ',' . $pageDescription . ',' . $pageKeywords . ','. $pageURL . '</p>';
  $fields = array($cobj->getCollectionID(), $pageName, $pageTitle, $pageDescription, $pageKeywords, $pageURL);
  fputcsv($fp, $fields);
}

// Récupères l'ID du fichier si un fichier a déjà été créé dans le Gestionnaire de fichiers
if ( file_exists($tempPath . '/coteo-seo-export-config.txt') ) {
  if ($fileConfig = fopen($tempPath . '/coteo-seo-export-config.txt', 'r')) {
    $fileConfigID = fgets($fileConfig);
    fclose($fileConfig);
  }
}

// Importe le fichier dans le Gestionnaire de fichiers
Loader::library("file/importer");
$fi = new FileImporter();

if ( File::getByID($fileConfigID) -> error ) {
  $f = $fi->import($fileExportUrl, $fileExportName);
} else {
  $fileExportObject = File::getByID($fileConfigID);
  $f = $fi->import($fileExportUrl, $fileExportName, $fileExportObject);
}

// Enregitre l'ID du fichier
if ($fileConfig = fopen($tempPath . '/coteo-seo-export-fileid.txt', 'w')) {
  fputs($fileConfig, $f->getFileID());
  fclose($fileConfig);
} else {
  echo "Echec de l'écriture du fichier";
}

 ?>
 <p><a href ="<?php echo File::getRelativePathFromID($f->getFileID()); ?>">Télécharger le CSV</a></p>
 <hr/>
 <h2>Import</h2>
 <p>Import des informations au format csv et mise à jour.</p>

 <?php
 $form = Loader::helper('form');
 ?>

<form method="post" action="<?php echo $this->action('fileUpload')?>" enctype="multipart/form-data">
  <p>Sélectionnez votre fichier d'import avec les mises à jour à effectuer.</p>
  <?php echo $form->file('myFile') ?>
  <input type="submit" name="submit" value="Télécharger" />
</form>

<?php
if (isset($fileInfo)) {
  ?>
  <p>
    Votre fichier fID <?php echo $fileInfo['fID'] ?> a été ajouté au Gestionnaire de fichier à l'emplacement suivant :<br />
    <a href="<?php  echo $fileInfo['link'] ?>" title="<?php echo $fileInfo['name'] ?>"><?php echo $fileInfo['name'] ?></a>
  </p>
  <?php
  // Traitement du fichier d'import
  $fileImportID = $fileInfo['fID'];
  echo $this->controller->fileImport($fileImportID);
  ?>
  <?php
}
?>
