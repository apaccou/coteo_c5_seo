<?php

// Todo : Important : Séparer éléments propres à Concrete5 du reste
require('http_request.class.php');

class SeoAudit
{
  public $startingHttpRequest;
  public $maxUrlsToScan = 20;
  private $_scanDepth;
  public static $analysedPagesCount;
  private $_urlsToScan = array();
  private $_urlsScanned = array();

  private $_crawlUrlCodeError = array();
  private $_crawlLinkInternalError = array();
  private $_crawlLinkExternalError = array();

  private $_externalLinkError = array();

  public function __construct($url , $scanDepth = 0)
  {
    $this->_scanDepth = $scanDepth;
    $this->analysedPagesCount = 0;

    // Todo : vérifier si impact d'analyser l'url avec ou sans / à la fin au départ
    if(substr($url, -1, 1) != '/') {
      $url = $url . '/';
    }

    $this->startingHttpRequest = new HTTPRequest($url);
    // évite le scan de http://localhost/ lorsqu'un domaine ou sous-domaine pointant vers la racine des fichiers du site n'est pas définit
    if($this->startingHttpRequest->_host == 'localhost') {
      $this->_urlsScanned[] = 'http://localhost/';
    }
    $this->addUrlToScan($url);
  }

  public function addUrlToScan($url)
  {
    // Todo : vérifier comportement avec Anchor sur Url Externe
    $url = $this->getAbsoluteLink($url, $this->_host);
    if(!in_array($url, $this->_urlsToScan) && !in_array($url, $this->_urlsScanned) ) {
      $this->_urlsToScan[] = $url;
    }
  }

  public function isExternalLink($url)
  {
    if($this->isAnchor($url)) {
      $parsedUrl = parse_url($url);
      // Suppresion de l'ancre
      $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . $parsedUrl['query'];
    }
    $r = new HttpRequest($url);
    if($r->_host == $this->startingHttpRequest->_host) {
      return false;
    } else {
      return true;
    }
  }

  public function isAnchor($url) {
    // Todo : voir si plus rapide avec http://php.net/manual/fr/function.parse-url.php
    if(strpos($url,"#") === false) {
      return false;
    } else {
      // echo "ANCRE  : #".explode( "#", $url )[1] . '<br/>';
      return true;
    }
  }

  public function analysePageAll()
  {
    foreach ($this->_urlsToScan as $key=>$url) {
      echo '<h2>Boucle '. __FUNCTION__ . '</h2>';
      echo 'analyse en cours de la page : ' . $key . ' / '. $url . '<br/>';
      // Limite
      if($this->analysedPagesCount == $this->maxUrlsToScan) {
        echo '<p>Limite maxUrlsToScan atteinte</p>';
        break;
      }
      // Vérifications
      if(in_array($url, $this->_urlsScanned)) {
        echo '<p>Url déjà scannée</p>';
        continue;
      }
      // Traitements
      $this->fetchUrl($url);
      $pageLinks = $this->getPageLinks($url);
      echo '<h2>Traitements '. __FUNCTION__ . '</h2>';
      // Boucle pour ajout des nouvelles urls à analyser
      foreach ($pageLinks as $link) {
        if($this->isExternalLink($link)) {
          echo 'Externe ==> ' . $link . '<br/>';
          // Todo : vérifier linkChecker
          // Todo : checker toutes les urls externe une seule fois à la fin pour éviter doublon d'analyse
          $this->checkExternalLinkError($link);
        } else {
          echo 'Interne ==> ' . $link . '<br/>';
          // Todo : vérifier que l'ajout se fasse bien pour les nouvelles URL qui contiennent une ancre
          if(!$this->isAnchor($link)) {
            $this->addUrlToScan($link);
          }
        }
        echo '<hr/>';
      }

echo '<br/>';
      // Mise à jour des variables
      if(!in_array($url, $this->_urlsScanned)) {
        $this->_urlsScanned[] = $url;
      }
      unset($this->_urlsToScan[$key]);
      $this->analysedPagesCount++;

      // Pause pour éviter saturation du serveur et de se faire bannir par fail2ban
      // Todo : pause sur les urls externes
      if($this->_host != 'localhost') {
        //sleep(1);
      }

      // Todo : limiter aux urls internes et optimiser
      if($this->isExternalLink($url)) {
        // rien
      } else {
        if($this->_scanDepth == 1) {
          $this->analysePageAll();
        }
      }
    }
  }

  public function reportAll() {
    echo '<h2> Analyse '. __FUNCTION__ . '</h2>';
    echo '<p>' . $this->analysedPagesCount . ' pages analysées</p>';
    echo '<h3>_urlsToScan : ' . count($this->_urlsToScan). '</h3>';
    echo '<pre>';
    var_dump($this->_urlsToScan);
    echo '</pre>';
    echo '<h3>_urlsScanned : ' . count($this->_urlsScanned) . '</h3>';
    echo '<pre>';
    var_dump($this->_urlsScanned);
    echo '</pre>';
    $this->reportCrawlUrlCodeError();
    $this->reportCrawlExternalLinkError();
  }

  public function checkIsUrl($url) {
    $pattern='|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
  	if(preg_match($pattern, $url) > 0) return true;
  	else return false;
  }

  // Check if URL exists and is Online
  public function checkIsAccesible($url) {
    $url = @parse_url($url);
    if (!$url) return false;

    $url = array_map('trim', $url);
    $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];

    $path = (isset($url['path'])) ? $url['path'] : '/';
    $path .= (isset($url['query'])) ? "?$url[query]" : '';

    if (isset($url['host']) && $url['host'] != gethostbyname($url['host'])) {

      $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);

      if (!$fp) return false; //socket not opened

      fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n"); //socket opened
      $headers = fread($fp, 4096);
      fclose($fp);

      if(preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers)){//matching header
        return true;
      }
      else return false;

    } // if parse url
    else return false;
  }

// Todo : comparer utiilité avec fonction : http://php.net/manual/fr/function.parse-url.php
  public function fetchUrl($url)
  {
    echo '<h2>'. __FUNCTION__ . '</h2>';
    $r = new HTTPRequest($url);
    //echo '<pre>' . $r->DownloadToString() . '</pre>';
    echo '<pre>';
    echo 'HTTP socket : ' . $r->_fp . '<br/>';
    echo 'full URL : ' . $r->_url . '<br/>';
    echo 'HTTP host : ' . $r->_host . '<br/>';
    echo 'protocol (HTTP/HTTPS) : ' . $r->_protocol. '<br/>';
    echo 'request URI : ' . $r->_uri . '<br/>';
    echo 'port : ' . $r->_port . '<br/>';
    echo '</pre>';
  }

  public function testDOMDocument($url) {
    $file = $url;
    $doc = new DOMDocument();
    $doc->loadHTMLFile($file);

    $elements = $doc->getElementsByTagName('a');

    if (!is_null($elements)) {
      foreach ($elements as $element) {
        echo "<br/>". $element->nodeName. ": ";

        $nodes = $element->childNodes;
        foreach ($nodes as $node) {
          echo $node->nodeValue. "\n";
        }
      }
    }
  }
  public function getPageLinks($url) {
    $file = $url;
    echo '<h2>'. __FUNCTION__ . '</h2>';
    echo 'récupération de tous les liens de la page : ' . $url . '<br/>';
    $doc = new DOMDocument();
    $doc->loadHTMLFile($file);

    // Todo : supprimer notes
    // Affichage du contenu texte de body
    // $elements = $doc->getElementsByTagName('body');
    // $plainText = $elements->textContent;

    // Todo : supprimer sorties d'aides

    $links = $doc->getElementsByTagName('a');
    foreach ($links as $link) {
           echo 'Lien brut : ' . $linkHref = $link->getAttribute('href');
           echo '<br/>';
           echo 'Lien absolu : ' . $linkHrefAbsolute = $this->getAbsoluteLink($linkHref, $this->startingHttpRequest->_protocol . '://' . $this->startingHttpRequest->_host . '/');
           echo '<br/>';
           $pageLinks[] = $linkHrefAbsolute;
    }
    return $pageLinks;
  }

  public function getHeaders($url)
  {
    // Todo : prendre en compte le cas des redirections
    // Todo : si pb de vitesse, voir CURL http://www.it-rem.ru/headers-curl-vs-get_headers-vs-fsockopen.html
    // et getHttpStatus https://www.packtpub.com/books/content/everything-package-concrete5

    // Note that get_headers **WILL follow redirections** (HTTP redirections). New headers will be appended to the array if $format=0. If $format=1 each redundant header will be an array of multiple values, one for each redirection.
    $headers = get_headers($url, 1);
    echo '<pre>';
    print_r($headers);
    echo '</pre>';

    // Todo : optimiser le transfert en récupérant et stockant les éléments une seule fois ou en ne récupérant que les headers ici

    // Par défaut, get_headers utilise une requête GET pour récupérer les
    // en-têtes. Si vous voulez plutôt envoyer une requête HEAD, vous pouvez le
    // faire en utilisant un contexte de flux :
    // stream_context_set_default(
    //     array(
    //         'http' => array(
    //             'method' => 'HEAD'
    //         )
    //     )
    // );
    // $headers = get_headers('http://example.com');
    return $headers;
  }

  //////////////////////
  // Analyse du crawl //
  //////////////////////

  public function crawlAnalyseAll($url) {
    // liste des URL en erreur (codes 301, 302, 404, 410, 500, etc.)
    $headers = $this->getHeaders($url);
    $code = $this->getHttpResponseCode($headers);
    // Todo : vérifier les codes à inscrire en erreur
    if($this->checkHttpResponseCode($code)) {
      $this->_crawlUrlCodeError[] = array($url, $code);
    }
    // liste des URL faisant un lien vers une URL en erreur
  }

  public function getHttpResponseCode($headers) {
    $code = substr($headers[0], 9, 3);
    return $code;
  }

  public function checkHttpResponseCode($code)
  {
    if ($code === NULL) {
      return false;
    }
    // Todo : voir si $text à conserver ou supprimer
    switch ($code) {
      case 100: $error = 1; $text = 'Continue'; break;
      case 101: $error = 1; $text = 'Switching Protocols'; break;
      case 200: $error = 1; $text = 'OK'; break;
      case 201: $error = 1; $text = 'Created'; break;
      case 202: $error = 1; $text = 'Accepted'; break;
      case 203: $error = 1; $text = 'Non-Authoritative Information'; break;
      case 204: $error = 1; $text = 'No Content'; break;
      case 205: $error = 1; $text = 'Reset Content'; break;
      case 206: $error = 1; $text = 'Partial Content'; break;
      case 300: $error = 1; $text = 'Multiple Choices'; break;
      case 301: $error = 0; $text = 'Moved Permanently'; break;
      case 302: $error = 0; $text = 'Moved Temporarily'; break;
      case 303: $error = 1; $text = 'See Other'; break;
      case 304: $error = 1; $text = 'Not Modified'; break;
      case 305: $error = 1; $text = 'Use Proxy'; break;
      case 400: $error = 1; $text = 'Bad Request'; break;
      case 401: $error = 1; $text = 'Unauthorized'; break;
      case 402: $error = 1; $text = 'Payment Required'; break;
      case 403: $error = 1; $text = 'Forbidden'; break;
      case 404: $error = 0; $text = 'Not Found'; break;
      case 405: $error = 1; $text = 'Method Not Allowed'; break;
      case 406: $error = 1; $text = 'Not Acceptable'; break;
      case 407: $error = 1; $text = 'Proxy Authentication Required'; break;
      case 408: $error = 1; $text = 'Request Time-out'; break;
      case 409: $error = 1; $text = 'Conflict'; break;
      case 410: $error = 0; $text = 'Gone'; break;
      case 411: $error = 1; $text = 'Length Required'; break;
      case 412: $error = 1; $text = 'Precondition Failed'; break;
      case 413: $error = 1; $text = 'Request Entity Too Large'; break;
      case 414: $error = 1; $text = 'Request-URI Too Large'; break;
      case 415: $error = 1; $text = 'Unsupported Media Type'; break;
      case 500: $error = 0; $text = 'Internal Server Error'; break;
      case 501: $error = 1; $text = 'Not Implemented'; break;
      case 502: $error = 1; $text = 'Bad Gateway'; break;
      case 503: $error = 1; $text = 'Service Unavailable'; break;
      case 504: $error = 1; $text = 'Gateway Time-out'; break;
      case 505: $error = 1; $text = 'HTTP Version not supported'; break;
      default:
      trigger_error('Unknown http status code ' . $code, E_USER_ERROR); // exit('Unknown http status code "' . htmlentities($code) . '"');
      return false;
    }
    return $error;
  }

  public function reportCrawlUrlCodeError() {
    // liste des URL en erreur (codes 301, 302, 404, 410, 500, etc.)
    echo '<h2>'. __FUNCTION__ . '</h2>';
    echo '<pre>';
    var_dump($this->_crawlUrlCodeError);
    echo '</pre>';
  }

  public function checkExternalLinkError($url) {
      if(!$this->checkIsAccesible($url)) {
        $this->_externalLinkError = array($url, $code);
      }
  }

  public function reportCrawlExternalLinkError() {
    // liste des URL faisant un lien vers une URL en erreur
    echo '<h2>'. __FUNCTION__ . '</h2>';
    echo '<pre>';
    var_dump($this->_externalLinkError);
    echo '</pre>';
  }

    //////////////////////
    // Analyse d //
    //////////////////////

  // Transfrom relative path into absolute URL using PHP
  public function getAbsoluteLink($rel, $base)
  {
      /* return if already absolute URL */
      if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

      /* queries and anchors */
      if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

      /* parse base URL and convert to local variables:
         $scheme, $host, $path */
      extract(parse_url($base));

      /* remove non-directory element from path */
      $path = preg_replace('#/[^/]*$#', '', $path);

      /* destroy path if relative url points to root */
      if ($rel[0] == '/') $path = '';

      /* dirty absolute URL */
      $abs = "$host$path/$rel";

      /* replace '//' or '/./' or '/foo/../' with '/' */
      $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
      for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

      /* absolute URL is ready! */
      return $scheme.'://'.$abs;
  }

  public function checkTitle()
  {
      // Vérification manuelle :
      // libellé non explicite du contenu de la page tels que "Bienvenue sur notre site web", "Homepage", "Accueil", ...
      // même titre pour toutes les pages du site

      // Conseil :
      // Toutes les pages de votre site doivent avoir un titre différent.
      // Un titre optimisé propose entre 5 et 10 mots descriptifs. Les <i>stop words</i> tel que "le", "la", "à", "au", "vos" ne comptent pas.
      // Les moteurs de recherche n'affichant que le début du titre dans les résultats, indiquez les mots-clés les plus importants au début afin qu'ils soient visibles par les internautes.
      // Structure conseillée pour les titres des pages internes : <title>[Contenu h1] - [Rubrique] - [Source]</title>
      // [Contenu h1]  reprend le titre éditiorial de la page (normalement inséré dans la balise h1)
      // [Rubrique] est la rubrique dans laquelle la page est proposée sur le site.
      // [Source] est le nom du site, sa marque.
  }
}

// Recherche de mots-clefs : http://www.webmarketing-com.com/2016/07/29/49878-5-meilleures-sources-mots-cles-marche-francais

// CrawlErrors (Google Webmaster Tools ou fonction ad hoc)
// https://searchenginewatch.com/category/seo/
// http://www.hobo-web.co.uk/optimize-website-navigation/
// http://www.webmarketing-conseil.fr/quels-criteres-site-bien-reference-google/
