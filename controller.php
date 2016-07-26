<?php
//namespace Concrete\Package\CoteoC5Seo;

//use Package;
//use Core;
//use Page;

defined('C5_EXECUTE') or die('Access Denied.');

//class Controller extends Package
class CoteoC5SeoPackage extends Package
{
    protected $pkgHandle = 'coteo_c5_seo';
    protected $appVersionRequired = '5.6.0';
    protected $pkgVersion = '0.0.1';

    public function getPackageName()
    {
        return t("Concrete 5.6 SEO Package");
    }

    public function getPackageDescription()
    {
        return t("A package that installs seo tools for 5.6");
    }

    public function getPackagehandle()
    {
        return $this->pkgHandle;
    }

    public function install()
    {
        $pkg = parent::install();
        //Install single page
        Loader::model('single_page');

        $path = '/dashboard/coteo/seo';
        $sp = Page::getByPath($path);
        if ($sp->isError() && $sp->getError() == COLLECTION_NOT_FOUND) {
           $sp = SinglePage::add($path, $pkg);
        }
    }

    public function on_start() {}

}
