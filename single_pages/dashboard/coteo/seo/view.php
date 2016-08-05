<?php
defined('C5_EXECUTE') or die('Access Denied.');


echo  Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('SEO'), t('SEO Tools By Coteo.')); ?>
<h1>Gérer les balises utiles au référencement</h1>
<p>Nom de page / Meta Title / Meta Description / Meta Keywords</p>
<br/>
<h2>Export</h2>
<p>Export des informations au format XML pour exploitation dans un tableur.</p>
<hr/>
<?php

// Création et Export du fichier XSD
$outputXSD = $this->controller->fileOutputXsd();

if ($outputXSD) {
  echo t('Génération du XSD réussie.');
  $exportXSD = $this->controller->fileExportXsd($outputXSD);
} else {
  echo t('Erreur lors de la génération du XSD.');
}

if ($exportXSD) {
  echo t('Export du XSD réussie.');
} else {
  echo t('Erreur lors de l\'export du XSD.');
}

// Création et Export du fichier XML
$outputXML = $this->controller->fileOutputXml();

if ($outputXML) {
  echo t('Génération du XML réussie.');
  $exportXML = $this->controller->fileExportXml($outputXML);
  $exportCSV = $this->controller->fileExportCsv($outputXML);
} else {
  echo t('Erreur lors de la génération du XML.');
}

if ($exportXML) {
  echo t('Export du XML réussie.');
} else {
  echo t('Erreur lors de l\'export du XML.');
}

if ($exportXML) {
  echo t('Export du XSV réussie.');
} else {
  echo t('Erreur lors de l\'export du CSV.');
}

// Affichage du lien de téléchargement du XML
if ($exportXML) {
  $form = Loader::helper('form');
?>
<form method="post" action="<?php echo $this->action('fileDownload'); ?>">
  <div class="alert alert-success" role="alert">Le fichier XML a été généré.</div>
  <?php echo $form->hidden("fileUrl", $this->controller->getFileExportXmlPath()); ?>
  <input type="submit" name="submit" value="Télécharger le XML" class="btn btn-primary" />
</form>
<?php
}

// Affichage du lien de téléchargement du CSV
if ($exportCSV) {
  $form = Loader::helper('form');
?>
<form method="post" action="<?php echo $this->action('fileDownload'); ?>">
  <div class="alert alert-success" role="alert">Le fichier CSV a été généré.</div>
  <?php echo $form->hidden("fileUrl", $this->controller->getFileExportCsvPath()); ?>
  <input type="submit" name="submit" value="Télécharger le CSV" class="btn btn-primary" />
</form>
<?php
}
 ?>

 <br/>
 <h2>Import</h2>
 <p>Import des informations au format XML et mises à jour.</p>
 <hr/>

 <?php
 $form = Loader::helper('form');
 ?>

<form method="post" action="<?php echo $this->action('fileUpload'); ?>" enctype="multipart/form-data" class="form-inline">
  <p>Sélectionnez votre fichier d'import avec les mises à jour à effectuer.</p>
  <?php echo $form->file('fileImport') ?>
  <?php echo $form->hidden("fileName", 'coteo-seo-import'); ?>
  <input type="submit" name="submit" value="Upload XML" class="btn btn-primary" />
</form>

<?php
if (isset($fileInfo) && isset($fileInfo['errorMessage'])) {
  echo '<div class="alert alert-danger" role="alert">' . $fileInfo['errorMessage'] .'</div>';
}

if (isset($fileInfo) && !isset($fileInfo['errorMessage'])) {
  ?>
  <p>
    Votre fichier fID <?php echo $fileInfo['fID'] ?> a été ajouté au Gestionnaire de fichier à l'emplacement suivant :<br />
    <a href="<?php  echo $fileInfo['link'] ?>" title="<?php echo $fileInfo['name'] ?>"><?php echo $fileInfo['name'] ?></a>
  </p>
  <?php
  // Traitement du fichier d'import
  $fileImportID = $fileInfo['fID'];
  // Todo : vérifier le format pour traiter du XML ou CSV si possible
  if($pagesDataUpdate = $this->controller->fileAnalyseXml($fileImportID)) {

    // Todo : formater l'aide
    // Todo : afficher l'aide avant la tentative d'import d'une MAJ
    echo '<h4><span class="label label-info">Aide</span> Importer les données XML dans Excel</h4>';
    echo '<ol><li>Ouvrir une feuille Excel Vierge.</li><li>Onglet [Données] <em>A partir d\'autres sources</em></li><li><em>Provenance : Importation de données XML</em></li></ol>';
    echo '<h4><span class="label label-info">Aide</span> Enregistrer les données XML depuis Excel</h4>';
    echo '<ol><li>Enregistrer sous Autres formats Données XML</li></ol>';
    // Todo : implémenter la fonction
    echo '<p>Réaliser un audit des changements, sans procéder aux changements.</p><br/>';
    echo '<p>Procéder aux changements.</p><br/>';
    echo '<form method="post" action="' . $this->action('runImport') . '">';
    $pagesDataUpdate = base64_encode(serialize($pagesDataUpdate));
    echo $form->hidden("pagesDataUpdate", $pagesDataUpdate);
    echo '<input type="submit" name="submit" value="Executer les changements" class="btn btn-primary" />';
    echo '</form>';
  }
  ?>
  <?php
}

if($this->controller->getTask() == 'runImport') {
 echo '<p>Mises à jour effectuées.</p>';
}
?>
