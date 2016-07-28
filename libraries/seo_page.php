<?php

class SeoPage
{
  public $ID;
  private $_cobj;

  public function __construct($pageID)
  {
    $this->ID = $pageID;
    $this->_cobj = Page::getByID($this->ID);
  }

  public function getPublicPageName()
  {
    $pageName = $this->_cobj->getCollectionName();
    $pageName = htmlspecialchars($pageName, ENT_COMPAT, APP_CHARSET);
    return $pageName;
  }

  public function getPublicPageTitle()
  {
    $pageTitle = $this->_cobj->getCollectionName();
    $pageTitle = htmlspecialchars($pageTitle, ENT_COMPAT, APP_CHARSET);
    $autoTitle = sprintf(PAGE_TITLE_FORMAT, SITE, $pageTitle);
    $pageTitle = $this->_cobj->getAttribute('meta_title') ? $this->_cobj->getAttribute('meta_title') : $autoTitle;
    return $pageTitle;
  }

  public function getPublicPageDescription()
  {
    $pageDescription = $this->_cobj->getCollectionDescription();
    $autoDesc = htmlspecialchars($pageDescription, ENT_COMPAT, APP_CHARSET);
    $pageDescription = $this->_cobj->getAttribute('meta_description') ? $this->_cobj->getAttribute('meta_description') : $autoDesc;
    // Todo : Vérifier si nécessaire pour le XML
$pageDescription = str_replace("\n","",$pageDescription);
$pageDescription = str_replace("\r","",$pageDescription);
    return $pageDescription;
  }

  public function getPublicPageKeywords ()
  {
    $pageKeywords = $this->_cobj->getAttribute('meta_keywords');
    return $pageKeywords;
  }

  public function getPublicPageUrl ()
  {
    $nh = Loader::helper('navigation');
    $pageUrl = $nh->getCollectionURL($this->_cobj);
    return $pageUrl;
  }
  
  // Todo : Class Function Documentation
  /**
  * Description de la fonction.
  * @param  string   $nomduparametre  Description du paramétre
  * @return boolean
  * @uses   Package
  */
}
