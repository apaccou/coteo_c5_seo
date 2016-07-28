<?php
defined('C5_EXECUTE') or die('Access Denied.');


echo  Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('SEO'), t('SEO Tools By Coteo.')); ?>
<h1>Gérer les balises utiles au référencement</h1>
<p>Nom de page / Meta Title / Meta Description / Meta Keywords</p>
<hr/>
<h2>Export</h2>
<p>Export des informations au format xml pour exploitation dans un tableur.</p>
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
} else {
  echo t('Erreur lors de la génération du XML.');
}

if ($exportXML) {
  echo t('Export du XML réussie.');
} else {
  echo t('Erreur lors de l\'export du XML.');
}

// Affichage du lien de téléchargement
if ($exportXML) {
  $form = Loader::helper('form');
?>
<form method="post" action="<?php echo $this->action('fileDownload'); ?>">
  <p>Le fichier XML a été généré.</p>
  <?php echo $form->hidden("fileUrl", $this->controller->getFileExportXmlPath()); ?>
  <input type="submit" name="submit" value="Télécharger le XML" />
</form>
<?php
}
 ?>

 <hr/>
 <h2>Import</h2>
 <p>Import des informations au format xml et mise à jour.</p>

 <?php
 $form = Loader::helper('form');
 ?>

<form method="post" action="<?php echo $this->action('fileUpload'); ?>" enctype="multipart/form-data">
  <p>Sélectionnez votre fichier d'import avec les mises à jour à effectuer.</p>
  <?php echo $form->file('fileImport') ?>
  <input type="submit" name="submit" value="Upload XML" />
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
  // Todo : vérifier le format pour traiter du XML ou CSV si possible
  if($pagesDataUpdate = $this->controller->fileAnalyseXml($fileImportID)) {

    // Todo : implémenter la fonction
    echo '<p>Réaliser un audit des changements, sans procéder aux changements.</p><br/>';
    echo '<p>Procéder aux changements.</p><br/>';
    echo '<form method="post" action="' . $this->action('runImport') . '">';
    $pagesDataUpdate = base64_encode(serialize($pagesDataUpdate));
    echo $form->hidden("pagesDataUpdate", $pagesDataUpdate);
    echo '<input type="submit" name="submit" value="Executer les changements" />';
    echo '</form>';
  }
  ?>
  <?php
}

if($this->controller->getTask() == 'runImport') {
 echo '<p>Mises à jour effectuées.</p>';
}
?>
