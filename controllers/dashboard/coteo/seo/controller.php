<?php
class DashboardCoteoSeoController extends Controller {

  public $fileExportXsdName = 'coteo-seo.xsd';
  public $fileExportXmlName = 'coteo-seo-export.xml';

  /**
  * Description de la fonction.
  * @param  string   $nomduparametre  Description du paramétre
  * @return boolean
  * @uses   Package
  */



  ///////////
  // Aides //
  //////////

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

      // Implémenter le contrôle de fichier
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
      $pageTitle = $cobj->getAttribute('meta_title') ? $cobj->getAttribute('meta_title') : $autoTitle;

      $pageDescription = $cobj->getCollectionDescription();
      $autoDesc = htmlspecialchars($pageDescription, ENT_COMPAT, APP_CHARSET);
      $pageDescription = $cobj->getAttribute('meta_description') ? $cobj->getAttribute('meta_description') : $autoDesc;
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

  ////////////
  // IMPORT //
  ///////////

  public function fileImportXML($fileImportID)
  {
    $fileImportObject = File::getByID($fileImportID);
    $fileImportUrl = $fileImportObject->getPath();
    if ( file_exists($fileImportUrl) ) {
      $fh = Loader::helper('file');
      $pages = $fh->getContents($fileImportUrl);
      $pages = new SimpleXMLElement($pages);

      $compteur = 0;
      $pageData = array();
      foreach ($pages as $page) {
        // on vérifie si la page existe
        if($cobj = Page::getByID((int) $page->pageID)) {

          $pageData[$cobj->getCollectionID()] = new ImportSeoPage((int) $page->pageID, (string) $page->pageName, (string) $page->pageTitle, (string) $page->pageDescription, (string) $page->pageKeywords);

          if($cobj->getCollectionName() != (string) $page->pageName) {
            $pageData[$cobj->getCollectionID()] = new ImportSeoPage((int) $page->pageID);
            echo 'Nom de page : ' . $cobj->getCollectionName() . ' ==> ' . $page->pageName . '</p>'; $compteur++;
          }
          // Todo : à vérifier / compléter
          if($cobj->getAttribute('meta_title') != (string) $page->pageTitle) {
            $pageData[$cobj->getCollectionID()] = new ImportSeoPage((int) $page->pageID);
            echo 'Titre de page : ' . $cobj->getAttribute('meta_title') . ' ==> ' . $page->pageTitle . '</p>'; $compteur++;
          }
          // Todo : à vérifier / compléter
          if($cobj->getAttribute('meta_description') != (string) $page->pageDescription) {
            $pageData[$cobj->getCollectionID()] = new ImportSeoPage((int) $page->pageID);
            echo 'Description de page : ' . $cobj->getAttribute('meta_description') . ' ==> ' . $page->pageDescription . '</p>'; $compteur++;
          }
          if($cobj->getAttribute('meta_keywords') != (string) $page->pageKeywords) {
            $pageData[$cobj->getCollectionID()] = new ImportSeoPage((int) $page->pageID);
            echo 'Keywords : ' . $cobj->getAttribute('meta_keywords') . ' ==> ' . $page->pageKeywords . '</p>'; $compteur++;
          }
        } else {
          continue;
        }
      }
      // Todo : vérifier si changement ou ajout
      echo '<p>' . $compteur . ' changements à effectuer.</p><br/>';
      echo '<pre>';
      var_dump($pageData);
      echo '</pre>';

    }
  }

// Fonction à corriger : pb avec les ""
  public function fileImportCSV($fileImportID)
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
   public function runImport($pageID, $pageName, $pageTitle, $pageDescription, $pageKeywords)
   {
     // Todo : sauvegarde BDD avant modification

     $cobj = Page::getByID($pageID);
     $data = array();
     $data['cName'] = $pageName;
     $cobj->update($data);
     $cobj->setAttribute('meta_title', nl2br(trim($pageTitle), true));
     $cobj->setAttribute('meta_description', nl2br(trim($pageDescription), true));
     $cobj->setAttribute('meta_keywords', nl2br(trim($pageKeywords), true));
   }

   ///////////
   // AUDIT //
   ///////////

}

class ImportSeoPage {
  public $ID;
  public $oldName;
  public $newName;
  public $oldTitle;
  public $newTitle;
  public $oldDescription;
  public $newDescription;
  public $oldKeywords;
  public $newKeywords;

  public function __construct($pageID, $pageName, $pageTitle, $pageDescription, $pageKeywords)
  {
    $this->ID = $pageID;
    $this->newName = $pageName;
    $this->newTitle = $pageTitle;
    $this->newDescription = $pageDescription;
    $this->newKeywords = $pageKeywords;

    $cobj = Page::getByID($this->ID);
    $this->oldName = $cobj->getCollectionName();
    // Todo : à vérifier / compléter
    $this->oldTitle = $cobj->getAttribute('meta_title');
    // Todo : à vérifier / compléter
    $this->oldDescription = $cobj->getAttribute('meta_description');
    $this->oldKeywords = $cobj->getAttribute('meta_keywords');
  }
}
