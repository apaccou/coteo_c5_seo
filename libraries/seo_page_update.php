<?php

class SeoPageUpdate
{
  public $change;
  public $ID;
  public $oldName;
  public $newName;
  public $oldTitle;
  public $newTitle;
  private $_oldCobj;
  public $oldDescription;
  public $newDescription;
  public $oldKeywords;
  public $newKeywords;
  public $url;

  public function __construct($pageID, $pageName = '', $pageTitle = '', $pageDescription = '', $pageKeywords = '')
  {
    $this->change = array();
    $this->ID = $pageID;
    $this->newName = $pageName;
    $this->newTitle = $pageTitle;
    $this->newDescription = $pageDescription;
    $this->newKeywords = $pageKeywords;

    $this->_oldCobj = new SeoPage($this->ID);
    $this->oldName = $this->_oldCobj->getPublicPageName();
    // Todo : à vérifier / compléter
    $this->oldTitle = $this->_oldCobj->getPublicPageTitle();
    // Todo : à vérifier / compléter
    $this->oldDescription = $this->_oldCobj->getPublicPageDescription();
    $this->oldKeywords = $this->_oldCobj->getPublicPageKeywords();
    $this->url = $this->_oldCobj->getPublicPageUrl();
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
    if($this->oldName != $this->newName ) {
      $this->change[] = 'Nom : ' . $this->oldName . ' ==> ' . $this->newName . '';
      return $this->change;
    } else {
      return null;
    }
  }

  public function checkChangeTitle()
  {
    if($this->oldTitle != $this->newTitle ) {
      $this->change[] = 'Titre : ' . $this->oldTitle . ' ==> ' . $this->newTitle . '';
      return $this->change;
    } else {
      return null;
    }
  }
  public function checkChangeDescription()
  {
    if($this->oldDescription != $this->newDescription ) {
      $this->change[] = 'Description : ' . $this->oldDescription . ' ==> ' . $this->newDescription . '';
      return $this->change;
    } else {
      return null;
    }
  }
  public function checkChangeKeywords()
  {
    if($this->oldKeywords != $this->newKeywords ) {
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

  // Todo : Class Function Documentation
  /**
  * Description de la fonction.
  * @param  string   $nomduparametre  Description du paramétre
  * @return boolean
  * @uses   Package
  */
}
