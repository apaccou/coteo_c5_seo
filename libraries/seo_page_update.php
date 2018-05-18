<?php

class SeoPageUpdate
{
  public $change;
  public $ID;
  private $_cobj;
  public $oldName;
  public $newName;
  public $oldTitle;
  public $newTitle;
  public $oldDescription;
  public $newDescription;
  public $oldKeywords;
  public $newKeywords;
  public $url;

  public function __construct($pageID, $pageName = '', $pageTitle = '', $pageDescription = '', $pageKeywords = '')
  {
    $this->change = array();
    $this->ID = $pageID;
    $this->_cobj = Page::getByID($this->ID);

    $this->newName = $pageName;
    $this->newTitle = $pageTitle;
    $this->newDescription = $pageDescription;
    $this->newKeywords = $pageKeywords;

    $this->oldName = $this->getPublicPageName();
    $this->oldTitle = $this->getPublicPageTitle();
    $this->oldDescription = $this->getPublicPageDescription();
    $this->oldKeywords = $this->getPublicPageKeywords();
    $this->url = $this->getPublicPageUrl();
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
    $autoTitle = sprintf(PAGE_TITLE_FORMAT, SITE, $pageTitle);
    $pageTitle = $this->_cobj->getAttribute('meta_title') ? $this->_cobj->getAttribute('meta_title') : $autoTitle;
    $pageTitle = htmlspecialchars($pageTitle, ENT_COMPAT, APP_CHARSET);
    return $pageTitle;
  }

  public function getPublicPageDescription()
  {
    $autoDesc = $this->_cobj->getCollectionDescription();
    $pageDescription = $this->_cobj->getAttribute('meta_description') ? $this->_cobj->getAttribute('meta_description') : $autoDesc;
    // Todo : Vérifier si nécessaire pour le XML
$pageDescription = str_replace("\n","",$pageDescription);
$pageDescription = str_replace("\r","",$pageDescription);
    $pageDescription = htmlspecialchars($pageDescription, ENT_COMPAT, APP_CHARSET);
    return $pageDescription;
  }

  public function getPublicPageKeywords ()
  {
    $pageKeywords = $this->_cobj->getAttribute('meta_keywords');
    $pageKeywords = htmlspecialchars($pageKeywords, ENT_COMPAT, APP_CHARSET);
    return $pageKeywords;
  }

  public function getPublicPageUrl ()
  {
    $nh = Loader::helper('navigation');
    $pageUrl = $nh->getCollectionURL($this->_cobj);
    return $pageUrl;
  }

  public function checkChangeAll()
  {
    $this->checkChangeName();
    $this->checkChangeTitle();
    $this->checkChangeDescription();
    $this->checkChangeKeywords();
  }

  public function checkChangeName()
  {
    if($this->oldName != htmlspecialchars($this->newName, ENT_COMPAT, APP_CHARSET)) {
      $this->change[] = 'Nom : ' . $this->oldName . ' ==> ' . $this->newName . '';
      return $this->change;
    } else {
      return null;
    }
  }

  public function checkChangeTitle()
  {
    if($this->oldTitle != htmlspecialchars($this->newTitle, ENT_COMPAT, APP_CHARSET)) {
      $this->change[] = 'Titre : ' . $this->oldTitle . ' ==> ' . $this->newTitle . '';
      return $this->change;
    } else {
      return null;
    }
  }
  public function checkChangeDescription()
  {
    if($this->oldDescription != htmlspecialchars($this->newDescription, ENT_COMPAT, APP_CHARSET) ) {
      $this->change[] = 'Description : ' . $this->oldDescription . ' ==> ' . $this->newDescription . '';
      return $this->change;
    } else {
      return null;
    }
  }
  public function checkChangeKeywords()
  {
    if($this->oldKeywords != htmlspecialchars($this->newKeywords, ENT_COMPAT, APP_CHARSET)) {
      $this->change[] = 'Keywords : ' . $this->oldKeywords . ' ==> ' . $this->newKeywords . '';
      return $this->change;
    } else {
      return null;
    }
  }
  public function updateAll()
  {
    $cobj = Page::getByID($this->ID);
    $data = array();

    if($this->checkChangeName()) {
      $data['cName'] = $this->newName;
      $cobj->update($data);
    }
    if($this->checkChangeTitle()) {
      $cobj->setAttribute('meta_title', nl2br(trim($this->newTitle), true));
    }
    if($this->checkChangeDescription()) {
      $cobj->setAttribute('meta_description', nl2br(trim($this->newDescription), true));
    }
    if($this->checkChangeKeywords()) {
      $cobj->setAttribute('meta_keywords', nl2br(trim($this->newKeywords), true));
    }
  }

  // Todo : Class Function Documentation
  /**
  * Description de la fonction.
  * @param  string   $nomduparametre  Description du paramétre
  * @return boolean
  * @uses   Package
  */
}
