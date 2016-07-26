<?php
class DashboardCoteoSeoController extends Controller {

  const FILE_XSD_NAME = 'coteo-seo.xsd';
  const FILE_XSD_URL = 'http://localhost/concrete5634/packages/coteo_c5_seo/coteo-seo.xsd';

  public function fileExportXSD()
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

  public function fileExportXML()
  {
    $XML = '<?xml version="1.0" encoding="UTF-8"?>';
    $XML .= '<site xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="' . self::FILE_XSD_URL . '">';

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
$pageDescription = str_replace("\n","",$pageDescription);
$pageDescription = str_replace("\r","",$pageDescription);

      $pageKeywords = $cobj->getAttribute('meta_keywords');

      $pageURL = $nh->getCollectionURL($cobj);

      //echo '<p>' . $cobj->getCollectionID() . ',' . $pageName . ',' . $pageTitle . ',' . $pageDescription . ',' . $pageKeywords . ','. $pageURL . '</p>';
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

  public function fileExportCSV()
  {
    // Tester la création automatique du CSV à partir du XML afin d'éviter de devoir maintenir les deux
  }

  public function fileImportXML($fileImportID)
  {
    $fileImportObject = File::getByID($fileImportID);
    $fileImportUrl = $fileImportObject->getPath();
    if ( file_exists($fileImportUrl) ) {
      $fh = Loader::helper('file');
      $pages = $fh->getContents($fileImportUrl);
      $pages = new SimpleXMLElement($pages);

      foreach ($pages as $page) {
        
        echo $page->pageID . '<br/>';
        echo $page->pageName . '<br/>';
        echo $page->pageTitle . '<br/>';
        echo $page->pageDescription . '<br/>';
        echo $page->pageKeywords . '<br/>';
        echo $page->pageURL . '<br/>';

        $cobj = Page::getByID($page->pageID);
        $data = array();
        $data['cName'] = $page->pageName;
        $cobj->update($data);
        $cobj->setAttribute('meta_title', nl2br(trim($page->pageTitle), true));
        $cobj->setAttribute('meta_description', nl2br(trim($page->pageDescription), true));
        $cobj->setAttribute('meta_keywords', nl2br(trim($page->pageKeywords), true));
      }
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
        //$file = $fi->import($_FILES['myFile']['tmp_name'], $_FILES['myFile']['name']);
$file = $fi->import($_FILES['myFile']['tmp_name'], 'coteo-seo-import.xml');

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

}
