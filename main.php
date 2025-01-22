<?php

/**
 * Main file of the "vector" template for DokuWiki
 *
 *
 * LICENSE: This file is open source software (OSS) and may be copied under
 *          certain conditions. See COPYING file for details or try to contact
 *          the author(s) of this file in doubt.
 *
 * @license GPLv2 (http://www.gnu.org/licenses/gpl2.html)
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 * @link https://www.dokuwiki.org/template:vector
 * @link https://www.dokuwiki.org/devel:templates
 * @link https://www.dokuwiki.org/devel:coding_style
 * @link https://www.dokuwiki.org/devel:environment
 * @link https://www.dokuwiki.org/devel:action_modes
 */


//check if we are running within the DokuWiki environment
if (!defined("DOKU_INC")){
    die();
}


/**
 * Stores the template wide action
 *
 * Different DokuWiki actions requiring some template logic. Therefore the
 * template has to know, what we are doing right now - and that is what this
 * var is for.
 *
 * Please have a look at the "detail.php" file in the same folder, it is also
 * influencing the var's value.
 *
 * @var string
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 */
$vector_action = "article";
//note: I used $_REQUEST before (cause DokuWiki controls and fills it. Normally,
//      using $_REQUEST is a possible security threat. For details, see
//      <http://www.suspekt.org/2008/10/01/php-53-and-delayed-cross-site-request-forgerieshijacking/>
//      and <https://forum.dokuwiki.org/post/16524>), but it did not work as
//      expected by me (maybe it is a reference and setting $vector_action
//      also changed the contents of $_REQUEST?!). That is why I switched back,
//      checking $_GET and $_POST like I did it before.
if (!empty($_GET["vecdo"])){
    $vector_action = (string)$_GET["vecdo"];
}elseif (!empty($_POST["vecdo"])){
    $vector_action = (string)$_POST["vecdo"];
}
if (!empty($vector_action) &&
    $vector_action !== "article" &&
    $vector_action !== "print" &&
    $vector_action !== "detail" &&
    $vector_action !== "cite"){
    //ignore unknown values
    $vector_action = "article";
}


/**
 * Stores the template wide context
 *
 * This template offers discussion pages via common articles, which should be
 * marked as "special". DokuWiki does not know any "special" articles, therefore
 * we have to take care about detecting if the current page is a discussion
 * page or not.
 *
 * @var string
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 */
$vector_context = "article";
if (preg_match("/^".tpl_getConf("vector_discuss_ns")."?$|^".tpl_getConf("vector_discuss_ns").".*?$/i", ":".getNS(getID()))){
    $vector_context = "discuss";
}


/**
 * Stores the name the current client used to login
 *
 * @var string
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 */
$loginname = "";
if (!empty($conf["useacl"])){
    if (isset($_SERVER["REMOTE_USER"]) && //no empty() but isset(): "0" may be a valid username...
        $_SERVER["REMOTE_USER"] !== ""){
        $loginname = $_SERVER["REMOTE_USER"]; //$INFO["client"] would not work here (-> e.g. if
                                              //current IP differs from the one used to login)
    }
}


//get needed language array
include DOKU_TPLINC."lang/en/lang.php";
//overwrite English language values with available translations
if (!empty($conf["lang"]) &&
    $conf["lang"] !== "en" &&
    file_exists(DOKU_TPLINC."/lang/".$conf["lang"]."/lang.php")){
    //get language file (partially translated language files are no problem
    //cause non translated stuff is still existing as English array value)
    include DOKU_TPLINC."/lang/".$conf["lang"]."/lang.php";
}


//detect revision
$rev = (int)$INFO["rev"]; //$INFO comes from the DokuWiki core
if ($rev < 1){
    $rev = (int)$INFO["lastmod"];
}


//get tab config
include DOKU_TPLINC."/conf/tabs.php";  //default
if (file_exists(DOKU_TPLINC."/user/tabs.php")){
    include DOKU_TPLINC."/user/tabs.php"; //add user defined
}


//get boxes config
include DOKU_TPLINC."/conf/boxes.php"; //default
if (file_exists(DOKU_TPLINC."/user/boxes.php")){
    include DOKU_TPLINC."/user/boxes.php"; //add user defined
}


//get button config
include DOKU_TPLINC."/conf/buttons.php"; //default
if (file_exists(DOKU_TPLINC."/user/buttons.php")){
    include DOKU_TPLINC."/user/buttons.php"; //add user defined
}


/**
 * Helper to render the tabs (like a dynamic XHTML snippet)
 *
 * @param array The tab data to render within the snippet. Each element is
 *        represented by a subarray:
 *        $array = array("tab1" => array("text"     => "hello world!",
 *                                       "href"     => "http://www.example.com"
 *                                       "nofollow" => true),
 *                       "tab2" => array("text"  => "I did it again",
 *                                       "href"  => DOKU_BASE."doku.php?id=foobar",
 *                                       "class" => "foobar-css"),
 *                       "tab3" => array("text"  => "I did it again and again",
 *                                       "href"  => wl("start", false, false, "&"),
 *                                       "class" => "foobar-css"),
 *                       "tab4" => array("text"      => "Home",
 *                                       "wiki"      => ":start"
 *                                       "accesskey" => "H"));
 *        Available keys within the subarrays:
 *        - "text" (mandatory)
 *          The text/label of the element.
 *        - "href" (optional)
 *          URL the element should point to (as link). Please submit raw,
 *          unencoded URLs, the encoding will be done by this function for
 *          security reasons. If the URL is not relative
 *          (= starts with http(s)://), the URL will be treated as external
 *          (=a special style will be used if "class" is not set).
 *        - "wiki" (optional)
 *          ID of a WikiPage to link (like ":start" or ":wiki:foobar").
 *        - "class" (optional)
 *          Name of an additional CSS class to use for the element content.
 *          Works only in combination with "text" or "href", NOT with "wiki"
 *          (will be ignored in this case).
 *        - "nofollow" (optional)
 *          If set to TRUE, rel="nofollow" will be added to the link if "href"
 *          is set (otherwise this flag will do nothing).
 *        - "accesskey" (optional)
 *          accesskey="<value>" will be added to the link if "href" is set
 *          (otherwise this option will do nothing).
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 * @return bool
 * @see _vector_renderButtons()
 * @see _vector_renderBoxes()
 * @link http://www.wikipedia.org/wiki/Nofollow
 * @link http://de.selfhtml.org/html/verweise/tastatur.htm#kuerzel
 * @link https://www.dokuwiki.org/devel:environment
 * @link https://www.dokuwiki.org/devel:coding_style
 */
function _vector_renderTabs($arr)
{
    //is there something useful?
    if (empty($arr) ||
        !is_array($arr)){
        return false; //nope, break operation
    }

    //array to store the created tabs into
    $elements = array();

    //handle the tab data
    foreach($arr as $li_id => $element){
        //basic check
        if (empty($element) ||
            !is_array($element) ||
            !isset($element["text"]) ||
            (empty($element["href"]) &&
             empty($element["wiki"]))){
            continue; //ignore invalid stuff and go on
        }
        $li_created = true; //flag to control if we created any list element
        $interim = "";
        //do we have an external link?
        if (!empty($element["href"])){
            //add URL
            $interim = "<a href=\"".hsc($element["href"])."\""; //@TODO: real URL encoding
            //add rel="nofollow" attribute to the link?
            if (!empty($element["nofollow"])){
                $interim .= " rel=\"nofollow\"";
            }
            //mark external link?
            if (substr($element["href"], 0, 4) === "http" ||
                substr($element["href"], 0, 3) === "ftp"){
                $interim .= " class=\"urlextern\"";
            }
            //add access key?
            if (!empty($element["accesskey"])){
                $interim .= " accesskey=\"".hsc($element["accesskey"])."\" title=\"[ALT+".hsc(strtoupper($element["accesskey"]))."]\"";
            }
            $interim .= "><span>".hsc($element["text"])."</span></a>";
        //internal wiki link
        }else if (!empty($element["wiki"])){
            $interim = "<a href=\"".hsc(wl(cleanID($element["wiki"])))."\"><span>".hsc($element["text"])."</span></a>";
        }
        //store it
        $elements[] = "\n        <li id=\"".hsc($li_id)."\"".(!empty($element["class"])
                                                             ? " class=\"".hsc($element["class"])."\""
                                                             : "").">".$interim."</li>";
    }

    //show everything created
    if (!empty($elements)){
        foreach ($elements as $element){
            echo $element;
        }
    }
    return true;
}


/**
 * Helper to render the boxes (like a dynamic XHTML snippet)
 *
 * @param array The box data to render within the snippet. Each box is
 *        represented by a subarray:
 *        $array = array("box-id1" => array("headline" => "hello world!",
 *                                          "xhtml"    => "I am <i>here</i>."));
 *        Available keys within the subarrays:
 *        - "xhtml" (mandatory)
 *          The content of the Box you want to show as XHTML. Attention: YOU
 *          HAVE TO TAKE CARE ABOUT FILTER EVENTUALLY USED INPUT/SECURITY. Be
 *          aware of XSS and stuff.
 *        - "headline" (optional)
 *          Headline to show above the box. Leave empty/do not set for none.
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 * @return bool
 * @see _vector_renderButtons()
 * @see _vector_renderTabs()
 * @link http://www.wikipedia.org/wiki/Nofollow
 * @link http://www.wikipedia.org/wiki/Cross-site_scripting
 * @link https://www.dokuwiki.org/devel:coding_style
 */
function _vector_renderBoxes($arr)
{
    //is there something useful?
    if (empty($arr) ||
        !is_array($arr)){
        return false; //nope, break operation
    }

    //array to store the created boxes into
    $boxes = array();

    //handle the box data
    foreach($arr as $div_id => $contents){
        //basic check
        if (empty($contents) ||
            !is_array($contents) ||
            !isset($contents["xhtml"])){
            continue; //ignore invalid stuff and go on
        }
        $interim  = "  <div id=\"".hsc($div_id)."\" class=\"portal\">\n";
        if (isset($contents["headline"])
            && $contents["headline"] !== ""){
            $interim .= "    <h5>".hsc($contents["headline"])."</h5>\n";
        }
        $interim .= "    <div class=\"body\">\n"
                   ."      <div class=\"dokuwiki\">\n" //dokuwiki CSS class needed cause we might have to show rendered page content
                   .$contents["xhtml"]."\n"
                   ."      </div>\n"
                   ."    </div>\n"
                   ."  </div>\n";
        //store it
        $boxes[] = $interim;
    }
    //show everything created
    if (!empty($boxes)){
        echo  "\n";
        foreach ($boxes as $box){
            echo $box;
        }
        echo  "\n";
    }

    return true;
}


/**
 * Helper to render the footer buttons (like a dynamic XHTML snippet)
 *
 * @param array The button data to render within the snippet. Each element is
 *        represented by a subarray:
 *        $array = array("btn1" => array("img"      => DOKU_TPL."static/img/button-vector.png",
 *                                       "href"     => "https://andreashaerter.com/",
 *                                       "width"    => 80,
 *                                       "height"   => 15,
 *                                       "title"    => "Andreas Haerter's website",
 *                                       "nofollow" => true),
 *                       "btn2" => array("img"   => DOKU_TPL."user/mybutton1.png",
 *                                       "href"  => wl("start", false, false, "&")),
 *                       "btn3" => array("img"   => DOKU_TPL."user/mybutton2.png",
 *                                       "href"  => "http://www.example.com");
 *        Available keys within the subarrays:
 *        - "img" (mandatory)
 *          The relative or full path of an image/button to show. Users may
 *          place own images within the /user/ dir of this template.
 *        - "href" (mandatory)
 *          URL the element should point to (as link). Please submit raw,
 *          unencoded URLs, the encoding will be done by this function for
 *          security reasons.
 *        - "width" (optional)
 *          width="<value>" will be added to the image tag if both "width" and
 *          "height" are set (otherwise, this will be ignored).
 *        - "height" (optional)
 *          height="<value>" will be added to the image tag if both "height" and
 *          "width" are set (otherwise, this will be ignored).
 *        - "nofollow" (optional)
 *          If set to TRUE, rel="nofollow" will be added to the link.
 *        - "title" (optional)
 *          title="<value>"  will be added to the link and image if "title"
 *          is set + alt="<value>".
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 * @return bool
 * @see _vector_renderButtons()
 * @see _vector_renderBoxes()
 * @link http://www.wikipedia.org/wiki/Nofollow
 * @link https://www.dokuwiki.org/devel:coding_style
 */
function _vector_renderButtons($arr)
{
    //array to store the created buttons into
    $elements = array();

    //handle the button data
    foreach($arr as $li_id => $element){
        //basic check
        if (empty($element) ||
            !is_array($element) ||
            !isset($element["img"]) ||
            !isset($element["href"])){
            continue; //ignore invalid stuff and go on
        }
        $interim = "";

        //add URL
        $interim = "<a href=\"".hsc($element["href"])."\""; //@TODO: real URL encoding
        //add rel="nofollow" attribute to the link?
        if (!empty($element["nofollow"])){
            $interim .= " rel=\"nofollow\"";
        }
        //add title attribute to the link?
        if (!empty($element["title"])){
            $interim .= " title=\"".hsc($element["title"])."\"";
        }
        $interim .= " target=\"_blank\"><img src=\"".hsc($element["img"])."\"";
        //add width and height attribute to the image?
        if (!empty($element["width"]) &&
            !empty($element["height"])){
            $interim .= " width=\"".(int)$element["width"]."\" height=\"".(int)$element["height"]."\"";
        }
        //add title and alt attribute to the image?
        if (!empty($element["title"])){
            $interim .= " title=\"".hsc($element["title"])."\" alt=\"".hsc($element["title"])."\"";
        } else {
            $interim .= " alt=\"\""; //alt is a mandatory attribute for images
        }
        $interim .= " border=\"0\" /></a>";

        //store it
        $elements[] = "      ".$interim."\n";
    }

    //show everything created
    if (!empty($elements)){
        echo  "\n";
        foreach ($elements as $element){
            echo $element;
        }
    }
    return true;
}

//workaround for the "jumping textarea" IE bug. CSS only fix not possible cause
//some DokuWiki JavaScript is triggering this bug, too. See the following for
//info:
//- <http://blog.andreas-haerter.com/2010/05/28/fix-msie-8-auto-scroll-textarea-css-width-percentage-bug>
//- <http://msdn.microsoft.com/library/cc817574.aspx>
if ($ACT === "edit" &&
    !headers_sent()){
    header("X-UA-Compatible: IE=EmulateIE7");
}

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo hsc($conf["lang"]); ?>" lang="<?php echo hsc($conf["lang"]); ?>" dir="<?php echo hsc($lang["direction"]); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php tpl_pagetitle(); echo " - ".hsc($conf["title"]); ?></title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<!-- Bootstrap Icons CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">

<?php
//show meta-tags
tpl_metaheaders();
echo "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\" />";

//include default or userdefined favicon
//
//note: since 2011-04-22 "Rincewind RC1", there is a core function named
//      "tpl_getFavicon()". But its functionality is not really fitting the
//      behaviour of this template, therefore I don't use it here.
if (file_exists(DOKU_TPLINC."user/favicon.ico")){
    //user defined - you might find http://tools.dynamicdrive.com/favicon/
    //useful to generate one
    echo "\n<link rel=\"shortcut icon\" href=\"".DOKU_TPL."user/favicon.ico\" />\n";
}elseif (file_exists(DOKU_TPLINC."user/favicon.png")){
    //note: I do NOT recommend PNG for favicons (cause it is not supported by
    //all browsers), but some users requested this feature.
    echo "\n<link rel=\"shortcut icon\" href=\"".DOKU_TPL."user/favicon.png);\" />\n";
}else{
    //default
    echo "\n<link rel=\"shortcut icon\" href=\"".DOKU_TPL."static/3rd/dokuwiki/favicon.ico\" />\n";
}

//include default or userdefined Apple Touch Icon (see <http://j.mp/sx3NMT> for
//details)
if (file_exists(DOKU_TPLINC."user/apple-touch-icon.png")){
    echo "<link rel=\"apple-touch-icon\" href=\"".DOKU_TPL."user/apple-touch-icon.png\" />\n";
}else{
    //default
    echo "<link rel=\"apple-touch-icon\" href=\"".DOKU_TPL."static/3rd/dokuwiki/apple-touch-icon.png\" />\n";
}

//load userdefined js?
if (tpl_getConf("vector_loaduserjs") && file_exists(DOKU_TPLINC."user/user.js")){
    echo "<script type=\"text/javascript\" charset=\"utf-8\" src=\"".DOKU_TPL."user/user.js\"></script>\n";
}

//show printable version?
if ($vector_action === "print"){
  //note: this is just a workaround for people searching for a print version.
  //      don't forget to update the styles.ini, this is the really important
  //      thing! BTW: good text about this: http://is.gd/5MyG5
  echo  "<link rel=\"stylesheet\" media=\"all\" type=\"text/css\" href=\"".DOKU_TPL."static/3rd/dokuwiki/print.css\" />\n"
       ."<link rel=\"stylesheet\" media=\"all\" type=\"text/css\" href=\"".DOKU_TPL."static/css/print.css\" />\n";
  if (file_exists(DOKU_TPL."user/print.css")){
      echo "<link rel=\"stylesheet\" media=\"all\" type=\"text/css\" href=\"".DOKU_TPL."user/print.css\" />\n";
  }
}

//load language specific css hacks?
if (file_exists(DOKU_TPLINC."lang/".$conf["lang"]."/style.css")){
  $interim = trim(file_get_contents(DOKU_TPLINC."lang/".$conf["lang"]."/style.css"));
  if (!empty($interim)){
      echo "<style type=\"text/css\" media=\"all\">\n".hsc($interim)."\n</style>\n";
  }
}
?>
<!--[if lte IE 8]><link rel="stylesheet" media="all" type="text/css" href="<?php echo DOKU_TPL; ?>static/css/screen_iehacks.css" /><![endif]-->
<!--[if lt IE 7]><style type="text/css">body{behavior:url("<?php echo DOKU_TPL; ?>static/3rd/vector/csshover.htc")}</style><![endif]-->
</head>
<body class="<?php
             //different styles/backgrounds for different page types
             switch (true){
                  //special: tech
                  case ($vector_action === "detail"):
                  case ($vector_action === "cite"):
                  case ($ACT === "media"): //var comes from DokuWiki
                  case ($ACT === "search"): //var comes from DokuWiki
                    echo "mediawiki ltr ns-1 ns-special ";
                    break;
                  //special: wiki
                  case (preg_match("/^wiki$|^wiki:.*?$/i", getNS(getID()))):
                    case "mediawiki ltr capitalize-all-nouns ns-4 ns-subject ";
                    break;
                  //discussion
                  case ($vector_context === "discuss"):
                    echo "mediawiki ltr capitalize-all-nouns ns-1 ns-talk ";
                    break;
                  //"normal" content
                  case ($ACT === "edit"): //var comes from DokuWiki
                  case ($ACT === "draft"): //var comes from DokuWiki
                  case ($ACT === "revisions"): //var comes from DokuWiki
                  case ($vector_action === "print"):
                  default:
                    echo "mediawiki ltr capitalize-all-nouns ns-0 ns-subject ";
                    break;
              } ?>skin-vector bg-light min-vh-100">

<div class="">
  <div class="main-wrapper"> <!-- Replace the row class with our custom wrapper -->
    <!-- Sidebar -->
    <div class="sidebar collapse" id="sidebar"> <!-- Removed 'collapse-horizontal' -->
      <!-- Header for mobile view -->
      <div class="d-md-none border-bottom p-2 position-relative">
        <!-- Close button positioned absolutely -->
        <button type="button" class="btn-close" aria-label="Close" id="sidebarCloseButton"></button>

        <!-- Top row with search -->
        <?php if (actionOK("search")){ ?>
        <form class="d-flex me-2" action="<?php echo wl(); ?>" accept-charset="utf-8" id="dw__search_mobile">
          <input type="hidden" name="do" value="search" />
          <div class="input-group input-group-sm" style="width: 160px;">
            <input class="form-control form-control-sm" type="search" id="qsearch__in_mobile" 
                   accesskey="f" name="id" placeholder="<?php echo hsc($lang['vector_search']); ?>..." />
            <button class="btn btn-outline-primary btn-sm" type="submit">
              <?php echo hsc($lang['vector_btn_search']); ?>
            </button>
          </div>
        </form>
        <?php } ?>

        <!-- Language selector below -->
        <?php if(file_exists(DOKU_PLUGIN.'translation/syntax.php')): ?>
        <div class="mt-2">
          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
              <?php echo hsc($conf['lang']); ?>
            </button>
            <ul class="dropdown-menu">
              <?php
              /** @var helper_plugin_translation $translation */
              $translation = plugin_load('helper', 'translation');
              if ($translation) {
                  $translations = $translation->getAvailableTranslations();
                  foreach ($translations as $lang => $name) {
                      echo '<li><a class="dropdown-item" href="'.wl($ID, array('lang' => $lang)).'">'.$name.'</a></li>';
                  }
              }
              ?>
            </ul>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Logo (hidden on mobile) -->
      <div class="logo-wrapper d-none d-md-block">
        <?php
        //include default or userdefined logo
        echo "<a href=\"".wl()."\" ";
        if (file_exists(DOKU_TPLINC."user/logo.png")){
            echo "style=\"background-image:url(".DOKU_TPL."user/logo.png);\"";
        }elseif (file_exists(DOKU_TPLINC."user/logo.gif")){
            echo "style=\"background-image:url(".DOKU_TPL."user/logo.gif);\"";
        }elseif (file_exists(DOKU_TPLINC."user/logo.jpg")){
            echo "style=\"background-image:url(".DOKU_TPL."user/logo.jpg);\"";
        }else{
            echo "style=\"background-image:url(".DOKU_TPL."static/3rd/dokuwiki/logo.png);\"";
        }
        echo " accesskey=\"h\" title=\"[ALT+H]\"></a>\n";
        ?>
      </div>
      
      <!-- Navigation boxes -->
      <div class="sidebar-nav">
        <?php
        if (!empty($_vector_boxes) && is_array($_vector_boxes)) {
          foreach ($_vector_boxes as $box_id => $box) {
            echo '<div id="'.hsc($box_id).'" class="portal mb-3">';
            
            // Box header
            if (!empty($box['headline'])) {
              echo '<h5 class="mb-2">'.hsc($box['headline']).'</h5>';
            }
            
            // Box content with Bootstrap classes
            echo '<div class="body">';
            echo '<div class="dokuwiki">';
            
            // Special handling for admin menu
            if ($box_id === 'p-admin') {
              $admin_content = $box['xhtml'];
              // Convert admin list to Bootstrap list
              $admin_content = str_replace(
                array('<ul class="admin_tasks">', '<li>', '</li>'),
                array('<ul class="list-group admin_tasks">', '<li class="list-group-item">', '</li>'),
                $admin_content
              );
              echo '<div class="admin-list-wrapper">';
              echo $admin_content;
              echo '</div>';
            }
            // Special handling for QR code
            else if ($box_id === 't-qrcode') {
              echo '<div class="text-center w-100">';
              echo $box['xhtml'];
              echo '</div>';
            }
            // Default handling for other boxes
            else {
              echo $box['xhtml'];
            }
            
            echo '</div>';
            echo '</div>';
            echo '</div>';
          }
        }
        ?>
      </div>
    </div>

    <!-- Main content -->
    <main class="main-content"> <!-- Replace Bootstrap column classes with our custom class -->
      <!-- Header -->
      <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
          <div class="d-flex align-items-center">
            <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
              <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Desktop language selector -->
            <?php if(file_exists(DOKU_PLUGIN.'translation/syntax.php')): ?>
            <div class="dropdown d-none d-md-block me-3">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <?php echo hsc($conf['lang']); ?>
              </button>
              <ul class="dropdown-menu">
                <?php
                if ($translation) {
                    foreach ($translations as $lang => $name) {
                        echo '<li><a class="dropdown-item" href="'.wl($ID, array('lang' => $lang)).'">'.$name.'</a></li>';
                    }
                }
                ?>
              </ul>
            </div>
            <?php endif; ?>

            <!-- Page actions -->
            <div class="btn-group me-3">
              <?php 
              if ($ACT == 'show') {
                if (actionOK('edit')): ?>
                  <a href="<?php echo wl($ID, array('do' => 'edit')); ?>" class="btn btn-outline-primary btn-sm">
                    <span class="d-none d-md-inline">Create/Edit</span>
                    <span class="d-md-none"><i class="bi bi-pencil-square"></i></span>
                  </a>
                <?php endif;
                
                // Add discussion button if enabled
                if (tpl_getConf("vector_discuss")): ?>
                  <a href="<?php echo wl($ID, array('do' => 'discussion')); ?>" class="btn btn-outline-info btn-sm">
                    <span class="d-none d-md-inline">Discussion</span>
                    <span class="d-md-none"><i class="bi bi-chat-dots"></i></span>
                  </a>
                <?php endif;
              } // Added missing closing bracket here
              ?>
              <?php if ($ACT == 'show'): ?>
                <a href="<?php echo wl($ID, array('do' => 'revisions')); ?>" class="btn btn-outline-secondary btn-sm">
                  <span class="d-none d-md-inline">Old revisions</span>
                  <span class="d-md-none"><i class="bi bi-clock-history"></i></span>
                </a>
              <?php endif; ?>
            </div>

            <!-- Theme toggle button -->
            <div class="navbar-nav ms-2">
              <div class="nav-item">
                <button class="btn btn-link nav-link" id="darkModeToggle">
                  <i class="bi bi-sun-fill" id="lightIcon"></i>
                  <i class="bi bi-moon-fill" id="darkIcon" style="display: none;"></i>
                </button>
              </div>
            </div>

          </div>

          <!-- Search -->
          <?php if (actionOK("search")){ ?>
          <form class="d-flex d-none d-md-inline" action="<?php echo wl(); ?>" accept-charset="utf-8" id="dw__search">
            <input type="hidden" name="do" value="search" />
            <div class="input-group" style="max-width: 300px;">
              <input class="form-control" type="search" id="qsearch__in" accesskey="f" name="id" 
                     placeholder="<?php echo hsc($lang['vector_search']); ?>..." />
              <button class="btn btn-outline-primary" type="submit">
                <?php echo hsc($lang['vector_btn_search']); ?>
              </button>
            </div>
          </form>
          <?php } ?>

          <!-- User menu -->
          <?php if (!empty($conf["useacl"])){ ?>
          <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                <?php echo hsc($loginname ? $loginname : $lang["btn_login"]); ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php
                if (!empty($conf["useacl"])){ //...makes only sense if there are users
                  //login?
                  if ($loginname === "") {
                    echo '<li><a class="dropdown-item" href="' . wl(cleanID(getId()), ['do' => 'login']) . '" rel="nofollow">' . hsc($lang["btn_login"]) . '</a></li>';
                  } else {
                    global $INFO;
                    //username and userpage
                    $profilePage = 'user:' . $loginname;
                    echo '<li><a class="dropdown-item" href="' . wl($profilePage) . '" rel="nofollow">' . hsc($loginname) . '</a></li>';
                    
                    //admin
                    if (!empty($INFO["isadmin"]) || !empty($INFO["ismanager"])){
                      echo '<li><a class="dropdown-item" href="' . wl('', ['do' => 'admin']) . '" rel="nofollow">Admin</a></li>';
                    }
                    
                    //profile
                    if (actionOK("profile")){ //check if action is disabled
                      echo '<li><a class="dropdown-item" href="' . wl('', ['do' => 'profile']) . '" rel="nofollow">Update Profile</a></li>';
                    }
                    
                    //logout
                    echo '<li><hr class="dropdown-divider"></li>';
                    echo '<li><a class="dropdown-item" href="' . wl('', ['do' => 'logout']) . '" rel="nofollow">Log Out</a></li>';
                  }
                }
                ?>
              </ul>
            </div>
          </div>
          <?php } ?>
        </div>
      </nav>

      <!-- Page content -->
      <div class="py-4">
        <?php 
        // Remove the following block to eliminate the duplicate TOC
        /*
        if ($toc = tpl_toc(true)) {
          echo '<div class="d-md-none mb-4">';
          echo '<div class="card">';
          echo '<div class="card-header">Table of Contents</div>';
          echo '<div class="card-body">';
          echo $toc;
          echo '</div>';
          echo '</div>';
        }
        */
        ?>

        <div class="row">

        <div class="row">
          <div class="<?php echo $toc ? 'col-md-9' : 'col-12'; ?>">
            <?php // ...existing content code... ?>
          </div>

          <?php if ($toc): ?>
          <!-- TOC for desktop -->
          <div class="col-md-3 d-none d-md-block">
            <div class="sticky-top pt-3">
              <div class="card">
                <div class="card-body">
                  <?php echo $toc; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <?php
        //show messages (if there are any)
        html_msgarea();
        //show site notice
        if (tpl_getConf("vector_sitenotice")){
            //detect wiki page to load as content
            if (!empty($transplugin) && //var comes from conf/boxes.php
                is_object($transplugin) &&
                tpl_getConf("vector_sitenotice_translate")){
                //translated site notice?
                $transplugin_langcur = $transplugin->hlp->getLangPart(cleanID(getId())); //current language part
                $transplugin_langs   = explode(" ", trim($transplugin->getConf("translations"))); //available languages
                if (empty($transplugin_langs) ||
                    empty($transplugin_langcur) ||
                    !is_array($transplugin_langs) ||
                    !in_array($transplugin_langcur, $transplugin_langs)) {
                    //current page is no translation or something is wrong, load default site notice
                    $sitenotice_location = tpl_getConf("vector_sitenotice_location");
                } else {
                    //load language specific site notice
                    $sitenotice_location = tpl_getConf("vector_sitenotice_location")."_".$transplugin_langcur;
                }
            }else{
                //default site notice, no translation
                $sitenotice_location = tpl_getConf("vector_sitenotice_location");
            }

            //we have to show a custom site notice
            if (empty($conf["useacl"]) ||
                auth_quickaclcheck(cleanID($sitenotice_location)) >= AUTH_READ){ //current user got access?
                echo "\n  <div id=\"siteNotice\" class=\"noprint\">\n";
                //get the rendered content of the defined wiki article to use as
                //custom site notice.
                $interim = tpl_include_page($sitenotice_location, false);
                if ($interim === "" ||
                    $interim === false){
                    //show creation/edit link if the defined page got no content
                    echo "[&#160;";
                    tpl_pagelink($sitenotice_location, hsc($lang["vector_fillplaceholder"]." (".hsc($sitenotice_location).")"));
                    echo "&#160;]<br />";
                }else{
                    //show the rendered page content
                    echo  "    <div class=\"dokuwiki\">\n" //dokuwiki CSS class needed cause we are showing rendered page content
                         .$interim."\n    "
                         ."</div>";
                }
                echo "\n  </div>\n";
            }
        }
        //show breadcrumps if enabled and position = top
        if ($conf["breadcrumbs"] == true &&
            $ACT !== "media" && //var comes from DokuWiki
            (empty($conf["useacl"]) || //are there any users?
             $loginname !== "" || //user is logged in?
             !tpl_getConf("vector_closedwiki")) &&
            tpl_getConf("vector_breadcrumbs_position") === "top"){
            echo "\n  <div class=\"catlinks noprint\"><p>\n    ";
            tpl_breadcrumbs();
            echo "\n  </p></div>\n";
        }
        //show hierarchical breadcrumps if enabled and position = top
        if ($conf["youarehere"] == true &&
            $ACT !== "media" && //var comes from DokuWiki
            (empty($conf["useacl"]) || //are there any users?
             $loginname !== "" || //user is logged in?
             !tpl_getConf("vector_closedwiki")) &&
            tpl_getConf("vector_youarehere_position") === "top"){
            echo "\n  <div class=\"catlinks noprint\"><p>\n    ";
            tpl_youarehere();
            echo "\n  </p></div>\n";
        }
        ?>

        <!-- start div id bodyContent -->
        <div id="bodyContent" class="dokuwiki">
          <!-- start rendered wiki content -->
          <?php
          //flush the buffer for faster page rendering, heaviest content follows
          if (function_exists("tpl_flush")) {
              tpl_flush(); //exists since 2010-11-07 "Anteater"...
          } else {
              flush(); //...but I won't loose compatibility to 2009-12-25 "Lemming" right now.
          }
          //decide which type of pagecontent we have to show
          switch ($vector_action){
              //"image details"
              case "detail":
                  include DOKU_TPLINC."inc_detail.php";
                  break;
              //"cite this article"
              case "cite":
                  include DOKU_TPLINC."inc_cite.php";
                  break;
              //show "normal" content
              default:
                  tpl_content(((tpl_getConf("vector_toc_position") === "article") ? true : false));
                  break;
          }
          ?>
          <!-- end rendered wiki content -->
          <div class="clearer"></div>
        </div>
        <!-- end div id bodyContent -->

        <?php
        //show breadcrumps if enabled and position = bottom
        if ($conf["breadcrumbs"] == true &&
            $ACT !== "media" && //var comes from DokuWiki
            (empty($conf["useacl"]) || //are there any users?
             $loginname !== "" || //user is logged in?
             !tpl_getConf("vector_closedwiki")) &&
            tpl_getConf("vector_breadcrumbs_position") === "bottom"){
            echo "\n  <div class=\"catlinks noprint\"><p>\n    ";
            tpl_breadcrumbs();
            echo "\n  </p></div>\n";
        }
        //show hierarchical breadcrumps if enabled and position = bottom
        if ($conf["youarehere"] == true &&
            $ACT !== "media" && //var comes from DokuWiki
            (empty($conf["useacl"]) || //are there any users?
             $loginname !== "" || //user is logged in?
             !tpl_getConf("vector_closedwiki")) &&
            tpl_getConf("vector_youarehere_position") === "bottom"){
            echo "\n  <div class=\"catlinks noprint\"><p>\n    ";
            tpl_youarehere();
            echo "\n  </p></div>\n";
        }
        ?>
      </div>
    </main>
  </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
  // Ensure the sidebar toggles correctly on mobile
  document.querySelector('.navbar-toggler').addEventListener('click', function() {
    document.getElementById('sidebar').classList.add('show');
  });

  // Close sidebar on mobile
  document.getElementById('sidebarCloseButton').addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('show');
  });

  // Theme switcher
  document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const lightIcon = document.getElementById('lightIcon');
    const darkIcon = document.getElementById('darkIcon');
    
    // Function to set theme
    function setTheme(theme) {
      document.documentElement.setAttribute('data-bs-theme', theme);
      localStorage.setItem('theme', theme);
      updateIcon(theme);
    }
    
    // Function to update icon
    function updateIcon(theme) {
      if (theme === 'dark') {
        lightIcon.style.display = 'none';
        darkIcon.style.display = 'inline';
      } else {
        lightIcon.style.display = 'inline';
        darkIcon.style.display = 'none';
      }
    }

    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    
    // Check if user has system-level preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    
    if (savedTheme) {
      // Use saved preference if it exists
      setTheme(savedTheme);
    } else {
      // Otherwise use system preference
      setTheme(prefersDark.matches ? 'dark' : 'light');
    }
    
    // Listen for system theme changes
    prefersDark.addEventListener('change', (e) => {
      if (!localStorage.getItem('theme')) {
        // Only update based on system changes if user hasn't set a preference
        setTheme(e.matches ? 'dark' : 'light');
      }
    });
    
    // Handle manual toggle
    darkModeToggle.addEventListener('click', function() {
      const currentTheme = document.documentElement.getAttribute('data-bs-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      setTheme(newTheme);
    });
  });
</script>

<?php
//provide DokuWiki housekeeping, required in all templates
tpl_indexerWebBug();

//include web analytics software
if (file_exists(DOKU_TPLINC."/user/tracker.php")){
    include DOKU_TPLINC."/user/tracker.php";
}
?>

</body>
</html>
