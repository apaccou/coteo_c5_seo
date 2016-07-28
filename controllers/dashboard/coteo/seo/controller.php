<?php

class DashboardCoteoSeoController extends Controller {

  public $fileExportXsdName = 'coteo-seo.xsd';
  public $fileExportXmlName = 'coteo-seo-export.xml';

  ///////////
  // AIDES //
  ///////////

  /**
  * Force le téléchargement d'un fichier.
  * @return mixed    Forces the download ou string
  */
  public function fileDownload()
  {
    $fileUrl = $_POST['fileUrl'];

    if (file_exists($fileUrl)) {
      $f = Loader::helper('file');
      $f->forceDownload($fileUrl);
      exit;
    } else {
      echo t('Unable to locate file %s', $fileUrl);
    }
  }

  /**
  * Import d'un fichier.
  * @return boolean
  */
  public function fileUpload()
  {
    Loader::library("file/importer");
    $fi = new FileImporter();

        /**
         * $fi->import handles importing the file into the Filemanager
         * 1st param: The temporary uploaded file
         * 2nd param: The actual filename
         * returns:   A file object
         */
        //$file = $fi->import($_FILES['fileImport']['tmp_name'], $_FILES['fileImport']['name']);
        // Todo : améliorer la fonction pour la rendre génréraliste en récupérant les informations en parammètres
$file = $fi->import($_FILES['fileImport']['tmp_name'], 'coteo-seo-import.xml');

        $path = $file->getRelativePath();
        $fID  = $file->fID;
        $name = $file->getFileName();
        $link = $file->getDownloadURL();

        $this->set('fileInfo', array('path' => $path,
                                     'fID'  => $fID,
                                     'name' => $name,
                                     'link' => $link
                                    ));

      // Todo : Implémenter le contrôle de fichier
      //echo $file->getExtension() . '<br/>';
      //echo $file->getType() . '<br/>';
      //coteo-seo-import.csv

      return true;

   }

  //////////
  // XML //
  /////////

  /**
  * Génére le fichier XSD.
  * @return string
  */
  public function fileOutputXsd()
  {
    $XSD = '<?xml version="1.0" encoding="UTF-8"?>';
    $XSD .= '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">';

    //<!-- definition of simple type elements -->
    $XSD .= '   <xsd:element name="pageID" type="xsd:positiveInteger" />';
    $XSD .= '   <xsd:element name="pageName" type="xsd:string" />';
    $XSD .= '   <xsd:element name="pageTitle" type="xsd:string" />';
    $XSD .= '   <xsd:element name="pageDescription" type="xsd:string" />';
    $XSD .= '   <xsd:element name="pageKeywords" type="xsd:string" />';
    $XSD .= '   <xsd:element name="pageURL" type="xsd:string" />';

    //<!-- definition of attributes -->

    //<!-- definition of complex type elements -->
    $XSD .= '   <xsd:element name="page">';
    $XSD .= '     <xsd:complexType>';
    $XSD .= '       <xsd:sequence>';
    $XSD .= '         <xsd:element ref="pageID" />';
    $XSD .= '         <xsd:element ref="pageName" />';
    $XSD .= '         <xsd:element ref="pageTitle" />';
    $XSD .= '         <xsd:element ref="pageDescription" />';
    $XSD .= '         <xsd:element ref="pageKeywords" />';
    $XSD .= '         <xsd:element ref="pageURL" />';
    $XSD .= '       </xsd:sequence>';
    $XSD .= '     </xsd:complexType>';
    $XSD .= '   </xsd:element>';

    $XSD .= '   <xsd:element name="site">';
    $XSD .= '     <xsd:complexType>';
    $XSD .= '       <xsd:sequence>';
    $XSD .= '         <xsd:element ref="page" maxOccurs="unbounded" />';
    $XSD .= '       </xsd:sequence>';
    $XSD .= '     </xsd:complexType>';
    $XSD .= '   </xsd:element>';

    $XSD .= '</xsd:schema>';

    return $XSD;
  }

  /**
  * Retourne le chemin vers le fichier XSD.
  * @return string
  */
  public function getFileExportXsdPath()
  {
    //détermine le chemin vers la racine du package
    $packagePath = Package::getByID($this->c->pkgID)->getPackagePath();

    $fileExportName = $this->fileExportXsdName;
    $fileExportUrl = $packagePath . '/' . $fileExportName;

    return $fileExportUrl;
  }

  /**
  * Retourne l'URL publique'vers le fichier XSD.
  * @return string
  */
  public function getFileExportXsdUrl()
  {
    $packageHandle = Package::getByID($this->c->pkgID)->getPackageHandle();

    $fileExportName = $this->fileExportXsdName;
    $fileExportUrl = BASE_URL . DIR_REL. '/packages/' . $packageHandle . '/' . $fileExportName;

    return $fileExportUrl;
  }


  /**
  * Exporte le fichier XSD.
  * @param  string $XSD Source au format XML
  * @return boolean
  */
  public function fileExportXsd($XSD)
  {
    $fileExportUrl = $this->getFileExportXsdPath();
    $filePointer = fopen($fileExportUrl, 'w');
    //add BOM to fix UTF-8 in Excel
    fputs($filePointer, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    fputs($filePointer, $XSD);
    fclose($filePointer);

    if (file_exists($fileExportUrl)) {
      $this->set('XSDExportUrl', $fileExportUrl);
      return true;
    } else {
      return false;
    }
  }

  /**
  * Génére le fichier XML.
  * @return string
  */
  public function fileOutputXml()
  {
    $XML = '<?xml version="1.0" encoding="UTF-8"?>';
    $XML .= '<site xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="' . $this->getFileExportXsdUrl() . '">';

    //liste les pages à exporter
    Loader::model('page_list');
    $pl = new PageList();
    $pages = $pl->get();

    $nh = Loader::helper('navigation');

    foreach ($pages as $cobj) {
      $pageName = $cobj->getCollectionName();
      $pageName = htmlspecialchars($pageName, ENT_COMPAT, APP_CHARSET);

      $pageTitle = $cobj->getCollectionName();
      $pageTitle = htmlspecialchars($pageTitle, ENT_COMPAT, APP_CHARSET);
      $autoTitle = sprintf(PAGE_TITLE_FORMAT, SITE, $pageTitle);
      $pageTitle = $cobj->getAttribute('meta_title') ? htmlspecialchars($cobj->getAttribute('meta_title'), ENT_COMPAT, APP_CHARSET) : $autoTitle;

      $pageDescription = $cobj->getCollectionDescription();
      $autoDesc = htmlspecialchars($pageDescription, ENT_COMPAT, APP_CHARSET);
      $pageDescription = $cobj->getAttribute('meta_description') ? htmlspecialchars($cobj->getAttribute('meta_description'), ENT_COMPAT, APP_CHARSET) : $autoDesc;
      // Nettoyage pour compatibilité sous Excel des retours à la ligne et retours chariots
      // Todo : Vérifier si nécessaire pour le XML
$pageDescription = str_replace("\n","",$pageDescription);
$pageDescription = str_replace("\r","",$pageDescription);

      $pageKeywords = $cobj->getAttribute('meta_keywords');

      $pageURL = $nh->getCollectionURL($cobj);

      // Débogguage
      //echo '<p>' . $cobj->getCollectionID() . ',' . $pageName . ',' . $pageTitle . ',' . $pageDescription . ',' . $pageKeywords . ','. $pageURL . '</p>'; exit;

      $XML .= '<page>';
      $XML .= '   <pageID>' . $cobj->getCollectionID() .'</pageID>';
      $XML .= '   <pageName>' . $pageName . '</pageName>';
      $XML .= '   <pageTitle>' . $pageTitle . '</pageTitle>';
      $XML .= '   <pageDescription>' . $pageDescription . '</pageDescription>';
      $XML .= '   <pageKeywords>' . $pageKeywords . '</pageKeywords>';
      $XML .= '   <pageURL>'. $pageURL . '</pageURL>';
      $XML .= '</page>';
    }
    $XML .= '</site>';

    return $XML;
  }

  /**
  * Retourne le chemin vers le fichier XML.
  * @return string
  */
  public function getFileExportXmlPath()
  {
    //détermine le chemin vers le fichier temporaire
    $tempPath = sys_get_temp_dir();

    $fileExportName = $this->fileExportXmlName;
    $fileExportUrl = $tempPath . '/' . $fileExportName;

    return $fileExportUrl;
  }

  /**
  * Exporte le fichier XML.
  * @param  string $XML Source au format XML
  * @return boolean
  */
  public function fileExportXml($XML)
  {
    // Todo : vérifier les droits d'écriture et gérer les cas d'erreur
    // Todo : vérifier l'utilité du BOM fix

    $fileExportUrl = $this->getFileExportXmlPath();
    $filePointer = fopen($fileExportUrl, 'w');
    //add BOM to fix UTF-8 in Excel
    fputs($filePointer, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    fputs($filePointer, $XML);
    fclose($filePointer);

    if (file_exists($fileExportUrl)) {
      return true;
    } else {
      return false;
    }
  }

  //////////
  // CSV //
  /////////

  /**
  * Génére le fichier CSV.
  * @return string
  */
  public function fileOutputCSV()
  {
    // Todo : Tester la création automatique du CSV à partir du XML afin d'éviter de devoir maintenir les deux
  }

  /**
  * Exporte le fichier CSV.
  * @return boolean
  */
  public function fileExportCSV()
  {
    // Todo : Tester la création automatique du CSV à partir du XML afin d'éviter de devoir maintenir les deux
  }

  /////////////
  // ANALYSE //
  /////////////

  public function fileAnalyseXml($fileImportID)
  {
    // Todo : enregistrer les retours dans une variable
    $pageData = array();

    $fileImportObject = File::getByID($fileImportID);
    $fileImportUrl = $fileImportObject->getPath();
    if ( file_exists($fileImportUrl) ) {
      $fh = Loader::helper('file');
      $pages = $fh->getContents($fileImportUrl);
      $pages = new SimpleXMLElement($pages);

      foreach ($pages as $page) {
        // on vérifie si la page existe
        if($cobj = Page::getByID((int) $page->pageID)) {

          $pageData[$cobj->getCollectionID()] = new SeoPageUpdate((int) $page->pageID, (string) $page->pageName, (string) $page->pageTitle, (string) $page->pageDescription, (string) $page->pageKeywords);
          $pageData[$cobj->getCollectionID()]->checkChangeAll();
          echo '<p><h3>Page ID : ' . $pageData[$cobj->getCollectionID()]->ID . '</h3><br/>URL : ' . $pageData[$cobj->getCollectionID()]->url . '</p>';
          if($changes = $pageData[$cobj->getCollectionID()]->change) {
            echo '<ul>';
            foreach ($changes as $change) {
              echo '<li>' . $change . '</li>';
            }
            echo '</ul>';
          } else {
            echo '<p>Pas de modifications</p>';
            // Todo : détruire la variable
          }

        } else {
          // il n'existe pas de page correspondant à l'ID du fichier
          continue;
        }
      }
    }
    return $pageData;
  }

// Todo : Fonction à corriger : pb avec les "" autour des éléments de plus de deux mots lors de l'analyse ou remplacer par sa génération automatique à partir du XML
  public function fileAnalyseCsv($fileImportID)
  {
    $fileImportObject = File::getByID($fileImportID);
    $fileImportUrl = $fileImportObject->getPath();

    if ( file_exists($fileImportUrl) ) {
      if ( $filePointer = fopen($fileImportUrl, 'r') ) {
        $row = 1;
        while (($data = fgetcsv($filePointer, 1000, ",", '"')) !== FALSE) {
          $num = count($data);
          if ($row == 1) {
            echo '$data : ' . $data[0] . $data[1];exit;
            if($data[0] == '"Page ID"') {echo 'Réussit !';}
          }
          echo "<p> $num champs à la ligne $row: <br /></p>\n";
          for ($c=0; $c < $num; $c++) {
            echo $data[$c] . "\n";
          }
          $row++;
        }

        fclose($filePointer);
      }
    }
        // $row = 1;
        // if ( ($handle = fopen($fileImportUrl, "r") ) !== FALSE) {
        //
        //     while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        //         $num = count($data);
        //         echo "<p> $num champs à la ligne $row: <br /></p>\n";
        //         $row++;
        //         for ($c=0; $c < $num; $c++) {
        //             echo $data[$c] . "<br />\n";
        //         }
        //     }
        //     fclose($handle);
        //
        // }
    	}


   ////////////////
   // TRAITEMENT //
   ////////////////

   public function runImport()
   {
     // Todo : sauvegarde BDD avant modification

     $pagesData = $_POST['pagesDataUpdate'];
     $pagesData = unserialize(base64_decode($pagesData));

     if($pagesData && is_array($pagesData)) {
       // Todo : compteur et infos sur Modification / Erreurs
       foreach ($pagesData as $page) {
         $pageExist = Page::getByID((int) $page->ID);
         if($pageExist->isError()){ continue;}
         $update = new SeoPageUpdate((int) $page->ID, (string) $page->newName, (string) $page->newTitle, (string) $page->newDescription, (string) $page->newKeywords);
         $update->updateAll();
       }
     }
   }

   ///////////
   // AUDIT //
   ///////////

   // Todo : fonctions à développer

}
