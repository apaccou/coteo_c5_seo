<?php

class DashboardCoteoSeoController extends Controller {

  public $fileExportXsdName = 'coteo-seo.xsd';
  public $fileExportXmlName = 'coteo-seo-export.xml';
  public $fileExportCsvName = 'coteo-seo-export.csv';

  public function on_before_render() {
    $htmlHelper = Loader::helper('html');
    $this->addHeaderItem($htmlHelper->css('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'));
  }

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

    $fileName = $_POST['fileName'];
    switch ($_FILES['fileImport']['type']) {
      case 'text/xml':
      $fileType = 'xml';
      break;
      case 'text/csv':
      $fileType = 'csv';
      break;
      default:
      break;
    }
    $fileName = $fileName . '.' . $fileType;

    $file = $fi->import($_FILES['fileImport']['tmp_name'], $fileName);

    if(is_int($file)) {
      // import retourne un code erreur $errorCode
      $errorMessage = FileImporter::getErrorMessage($file);
      $this->set('fileInfo', array('errorMessage' => $errorMessage));
      return false;
    } else {
      $path = $file->getRelativePath();
      $fID  = $file->fID;
      $name = $file->getFileName();
      $link = $file->getDownloadURL();

      $this->set('fileInfo', array('path' => $path,
                                   'fID'  => $fID,
                                   'name' => $name,
                                   'link' => $link
                                  ));
      return true;
      }
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

    // Liste les pages à exporter
    Loader::model('page_list');
    $pl = new PageList();
    $pages = $pl->get();

    foreach ($pages as $cobj) {
      $seoPage = new SeoPageUpdate($cobj->getCollectionID());

      $pageName = $seoPage->getPublicPageName();
      $pageTitle = $seoPage->getPublicPageTitle();
      $pageDescription = $seoPage->getPublicPageDescription();
      $pageKeywords = $seoPage->getPublicPageKeywords();

      $pageURL = $seoPage->getPublicPageUrl();

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
  public function fileExportXml($xml)
  {
    // Todo : vérifier les droits d'écriture et gérer les cas d'erreur
    // Todo : vérifier l'utilité du BOM fix

    $fileExportUrl = $this->getFileExportXmlPath();
    $filePointer = fopen($fileExportUrl, 'w');
    //add BOM to fix UTF-8 in Excel
    fputs($filePointer, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    fputs($filePointer, $xml);
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
  * Retourne le chemin vers le fichier CSV.
  * @return string
  */
  public function getFileExportCsvPath()
  {
    //détermine le chemin vers le fichier temporaire
    $tempPath = sys_get_temp_dir();

    $fileExportName = $this->fileExportCsvName;
    $fileExportUrl = $tempPath . '/' . $fileExportName;

    return $fileExportUrl;
  }

  /**
  * Génére et Exporte le fichier CSV à partir du XML.
  * @param  string $XML Source au format XML
  * @return boolean
  */
  public function fileExportCsv($xml)
  {
    // Todo : vérifier les droits d'écriture et gérer les cas d'erreur
    $fileExportUrl = $this->getFileExportCsvPath();
    $filePointer = fopen($fileExportUrl, 'w');
    //add BOM to fix UTF-8 in Excel
    fputs($filePointer, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    if ($xml = simplexml_load_string($xml)) {
      // Todo : Ajouter les entêtes au fichier CSV
      //print_r($xml);
      //echo '<br/> TEST ' . $xml->page[0]->pageID->getName() . ' TEST<br/>';
      // Todo : corriger les pb d'encodage, notamment des '-'
      foreach ($xml->page as $item) {
        fputcsv($filePointer, get_object_vars($item),',','"');
      }
    }
    //fputs($filePointer, $csv);
    fclose($filePointer);

    if (file_exists($fileExportUrl)) {
      return true;
    } else {
      return false;
    }
  }

  /////////////
  // ANALYSE //
  /////////////

  public function fileAnalyseXml($fileImportID)
  {
    // Todo : enregistrer les retours dans une variable
    // Todo : ajouter couleurs en fonctions des modifications
    // Todo : ajouter possibilité de filtrer ajout/modification/suppression ?
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
          echo '<div class="panel panel-default">';
          echo '  <div class="panel-heading"><h3 class="panel-title">Page ID : ' . $pageData[$cobj->getCollectionID()]->ID . '</h3><br/>URL : ' . $pageData[$cobj->getCollectionID()]->url . '</div>';
          echo '  <div class="panel-body">';
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
          echo '  </div>';
          //echo ' <div class="panel-footer"></div>';
          echo '</div>';

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
    $pageData = array();

    $fileImportObject = File::getByID($fileImportID);
    $fileImportUrl = $fileImportObject->getPath();

    if ( file_exists($fileImportUrl) ) {
      if ( $filePointer = fopen($fileImportUrl, 'r') ) {
        $row = 1;
        while (($data = fgetcsv($filePointer, 1000, ",", '"')) !== FALSE) {
          // $num = count($data);
          // echo "<p> $num champs à la ligne $row: <br /></p>\n";
          // for ($c=0; $c < $num; $c++) {
          //   echo $data[$c] . "<br />\n";
          // }
          $data['pageID'] = $data[0];
          $data['pageName'] = $data[1];
          $data['pageTitle'] = $data[2];
          $data['pageDescription'] = $data[3];
          $data['pageKeywords'] = $data[4];
          $data['pageURL'] = $data[5];

          if($cobj = Page::getByID((int) $data['pageID'])) {
            $pageData[$cobj->getCollectionID()] = new SeoPageUpdate((int) $data['pageID'], (string) $data['pageName'], (string) $data['pageTitle'], (string) $data['pageDescription'], (string) $data['pageKeywords']);
            $pageData[$cobj->getCollectionID()]->checkChangeAll();
          }
          $row++;
        }
        fclose($filePointer);
      }
    }
    return $pageData;
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
     // Todo : retour sur la vue précédente ?
   }

   ///////////
   // AUDIT //
   ///////////

   // Todo : fonctions à développer

}
