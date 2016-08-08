<?php

// Todo : classe à construire
// Important : Séparer éléments propres à Concrete5 du reste
// Todo : voir si possibilité de require HttpRequest pour s'assurer de son chargement

class SeoAudit
{
  public $startingHttpRequest;
  private $_scanDepth;
  public $analysedPagesCount;
  private $_urlsToScan = array();
  private $_urlsScanned = array();

  // Récup URL Page ==> Analayse de la page
  // Récup URL ACCUEIL ==> Récupération des URLs du SITE ==> Analyse de la page

  public function __construct($url , $scanDepth = 0)
  {
    $this->_scanDepth = $scanDepth;
    $this->analysedPagesCount = 0;

    $this->startingHttpRequest = new HTTPRequest($url);
    $this->addUrlToScan($url);
  }

  public function addUrlToScan($url)
  {
    $url = $this->rel2abs($url, $this->_host);
    if(!in_array($url, $this->_urlsToScan)) {
      $this->_urlsToScan[] = $url;
    }
  }

  public function isExternalLink($url)
  {
    $r = new HttpRequest($url);
    if($r->_host == $this->startingHttpRequest->_host) {
      return false;
    } else {
      return true;
    }
  }

  public function analysePageAll()
  {
    foreach ($this->_urlsToScan as $key=>$url) {
      echo '<h2>'. __FUNCTION__ . '</h2>';
      echo 'analyse en cours de la page : ' . $url . '<br/>';
      // Limite
      if($this->analysedPagesCount >= 20) {
        continue;
      }
      // Traitements
      $this->fetchUrl($url);
      // Mise à jour des variables
      if(!in_array($url, $this->_urlsScanned)) {
        $this->_urlsScanned[] = $url;
      }
      unset($this->_urlsToScan[$key]);
      $this->analysedPagesCount++;

      // Pause pour éviter saturation du serveur et de se faire bannir par fail2ban
      // Todo : pause sur les urls externes
      if($this->_host != 'localhost') {
        sleep(1);
      }

      // Boucle pour ajout des nouvelles urls à analyser
      // Todo : limiter aux urls internes
      if($this->isExternalLink($url)) {
        // Todo :linckCkecker ici ?
        echo '<p>Lancement linckCkecker</p>';
      } else {
        if($this->_scanDepth = 1) {
          $this->getPageLinks($url);
        }
      }
    }
    echo '<h2>'. __FUNCTION__ . '</h2>';
    echo $this->analysedPagesCount;
    echo '<pre>';
    var_dump($this->_urlsToScan);
    echo '</pre>';
    echo '<pre>';
    var_dump($this->_urlsScanned);
    echo '</pre>';
  }

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

  public function test($url) {
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
    echo 'analyse en cours de la page : ' . $url . '<br/>';
    $doc = new DOMDocument();
    $doc->loadHTMLFile($file);

    // Affichage du contenu texte de body
    // $elements = $doc->getElementsByTagName('body');
    // $plainText = $elements->textContent;

    $links = $doc->getElementsByTagName('a');
    echo '<h2>'. __FUNCTION__ . '</h2>';
    foreach ($links as $link) {
           echo 'Lien brut : ' . $linkHref = $link->getAttribute('href');
           echo '<br/>';
           echo 'Lien absolu : ' . $linkHrefAbsolute = $this->rel2abs($linkHref, $this->startingHttpRequest->_protocol . '://' . $this->startingHttpRequest->_host);
           echo '<br/>';
           $this->addUrlToScan($linkHrefAbsolute);
    }
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

  public function getHttpResponseCode($headers) {
    $code = substr($headers[0], 9, 3);
    return $code;
  }

  public function checkHttpResponseCode($code)
  {
    if ($code === NULL) {
      return false;
    }

    switch ($code) {
      case 100: $text = 'Continue'; break;
      case 101: $text = 'Switching Protocols'; break;
      case 200: $text = 'OK'; break;
      case 201: $text = 'Created'; break;
      case 202: $text = 'Accepted'; break;
      case 203: $text = 'Non-Authoritative Information'; break;
      case 204: $text = 'No Content'; break;
      case 205: $text = 'Reset Content'; break;
      case 206: $text = 'Partial Content'; break;
      case 300: $text = 'Multiple Choices'; break;
      case 301: $text = 'Moved Permanently'; break;
      case 302: $text = 'Moved Temporarily'; break;
      case 303: $text = 'See Other'; break;
      case 304: $text = 'Not Modified'; break;
      case 305: $text = 'Use Proxy'; break;
      case 400: $text = 'Bad Request'; break;
      case 401: $text = 'Unauthorized'; break;
      case 402: $text = 'Payment Required'; break;
      case 403: $text = 'Forbidden'; break;
      case 404: $text = 'Not Found'; break;
      case 405: $text = 'Method Not Allowed'; break;
      case 406: $text = 'Not Acceptable'; break;
      case 407: $text = 'Proxy Authentication Required'; break;
      case 408: $text = 'Request Time-out'; break;
      case 409: $text = 'Conflict'; break;
      case 410: $text = 'Gone'; break;
      case 411: $text = 'Length Required'; break;
      case 412: $text = 'Precondition Failed'; break;
      case 413: $text = 'Request Entity Too Large'; break;
      case 414: $text = 'Request-URI Too Large'; break;
      case 415: $text = 'Unsupported Media Type'; break;
      case 500: $text = 'Internal Server Error'; break;
      case 501: $text = 'Not Implemented'; break;
      case 502: $text = 'Bad Gateway'; break;
      case 503: $text = 'Service Unavailable'; break;
      case 504: $text = 'Gateway Time-out'; break;
      case 505: $text = 'HTTP Version not supported'; break;
      default:
      trigger_error('Unknown http status code ' . $code, E_USER_ERROR); // exit('Unknown http status code "' . htmlentities($code) . '"');
      return false;
    }
    return $text;
  }

  // Transfrom relative path into absolute URL using PHP
  public function rel2abs($rel, $base)
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
