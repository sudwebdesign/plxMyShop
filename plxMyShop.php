<?php
/**
 * Plugin plxMyShop
 * Compatible urlRewrite & Multilingue
 * @author David L
 **/
class plxMyShop extends plxPlugin {

 public $aProds = array(); # Tableau de tous les produits
 public $donneesModeles = array();
 public $plxMotor;
 public $cheminImages;
 public $idProduit;
 public $shortcode = "boutonPanier";
 public $shortcodeactif = false;

 public function __construct($default_lang){

  # récupération de la langue si plugin plxMyMultilingue présent
  $this->lang="";
  if(defined('PLX_MYMULTILINGUE')) {
   $lang = plxMyMultiLingue::_Lang();
   if(!empty($lang)) {
    if(isset($_SESSION['default_lang']) AND $_SESSION['default_lang']!=$lang) {
     $this->lang = $lang.'/';
    }
   }
  }

  # appel du constructeur de la classe plxPlugin (obligatoire)
  parent::__construct($default_lang);
  # Accès au menu admin réservé au profil administrateur et gestionnaire
  $this->setAdminProfil(PROFIL_ADMIN, PROFIL_MANAGER);
  # droits pour accèder à la page config.php du plugin
  $this->setConfigProfil(PROFIL_ADMIN);
  # Personnalisation du menu admin
  $this->setAdminMenu(
   ($this->getParam('shop_name') !== "" ? $this->getParam('shop_name') : "MyShop") . ' ' . $this->getInfo('version')
   , 5
   , $this->getlang('L_ADMIN_MENU_TOOTIP')
  );
//hook PluXml
  $this->addHook('plxMotorPreChauffageBegin', 'plxMotorPreChauffageBegin');
  $this->addHook('plxShowConstruct', 'plxShowConstruct');
  $this->addHook('plxShowPageTitle', 'plxShowPageTitle');
  $this->addHook('plxShowStaticListEnd', 'plxShowStaticListEnd');
  $this->addHook('SitemapStatics', 'SitemapStatics');
  $this->addHook('plxMotorParseArticle', 'plxMotorParseArticle');
  $this->addHook('AdminPrepend', 'AdminPrepend');
  $this->addHook('AdminTopBottom', 'AdminTopBottom');
  $this->addHook('plxShowStaticContent', 'plxShowStaticContent');
  $this->addHook('ThemeEndBody', 'ThemeEndBody');

  $this->getProducts();

  if (!is_dir(PLX_ROOT.(empty($this->getParam('racine_commandes'))?'data/commandes/':$this->getParam('racine_commandes')))){
   mkdir(PLX_ROOT.(empty($this->getParam('racine_commandes'))?'data/commandes/':$this->getParam('racine_commandes')), 0755, true);
  }
  if (!is_file(PLX_ROOT.(empty($this->getParam('racine_commandes'))?'data/commandes/':$this->getParam('racine_commandes')).'index.html')){
   $mescommandeindex = fopen(PLX_ROOT.(empty($this->getParam('racine_commandes'))?'data/commandes/':$this->getParam('racine_commandes')).'index.html', 'w+');
   fclose($mescommandeindex);
  }

  // méthodes de paiement
  $tabMethodespaiement = array(
   "cheque" => array(
    "libelle" => $this->getlang('L_PAYMENT_CHEQUE') ,
    "codeOption" => "payment_cheque",
   ),
   "cash" => array(
    "libelle" => $this->getlang('L_PAYMENT_CASH') ,
    "codeOption" => "payment_cash",
   ),
   "paypal" => array(
    "libelle" => $this->getlang('L_PAYMENT_PAYPAL'),
    "codeOption" => "payment_paypal",
   ),
  );

  $tabChoixMethodespaiement = array();
  foreach ($tabMethodespaiement as $codeMethodespaiement => $m){
   if ("1" === $this->getParam($m["codeOption"])){
    $tabChoixMethodespaiement[$codeMethodespaiement] = $m;
   }
  }
  $this->donneesModeles["tabChoixMethodespaiement"] = $tabChoixMethodespaiement;
//hook plxMyShop
  $this->addHook('plxMyShopShowMiniPanier', 'plxMyShopShowMiniPanier');
  if($this->getParam('localStorage')){//MyshopCookie
   $this->addHook('plxMyShopPanierCoordsMilieu', 'inlineHtml');
   $this->addHook('plxMyShopPanierFin', 'inlineJs');
  }
  if($this->getParam('cookie')){//MyshopCookie
   $this->addHook('Index', 'Index');
   $this->addHook('IndexEnd', 'IndexEnd');
  }
 }

 public function plxMyShopShowMiniPanier(){
  //var_dump($this->plxMotor->get);exit;
  $class = $this->plxMotor->get=='boutique/panier'?'active':'noactive';
?>
  <h3<?php if ($class=="active") echo' class="red"'; ?>>
   <span><img src="<?php echo PLX_PLUGINS.$this->plug['name']; ?>/icon.png" style="float:left;"></span>&nbsp;<?php $this->lang('L_PUBLIC_BASKET'); ?></h3>
<?php
if (isset($_SESSION[$this->plug['name']]["ncart"]) && $_SESSION[$this->plug['name']]["ncart"]>0  && !empty($_SESSION[$this->plug['name']]["prods"])){
   echo '<ul class="cat-list unstyled-list"><li><ul>'.PHP_EOL;
   foreach($_SESSION[$this->plug['name']]["prods"] as $k => $v){
    echo '<li>'.$this->aProds[$k]['name'].'<sup><span class="badge">'.$v.'</span></sup></li>'.PHP_EOL;
   }
   echo '</ul></li></ul>
   <p>'.($class!="active"?'<a class="button blue" href="'.$this->plxMotor->urlRewrite('?'.$this->lang.'boutique/panier#panier').'" title="'.$this->getLang('L_PUBLIC_BASKET_MINI_TITLE').'">'.$this->getLang('L_PUBLIC_BASKET_MINI').'</a>':'').'</p>'.PHP_EOL;
  }else{
   echo '<ul class="lastart-list unstyled-list"><li><em>'.$this->getLang('L_PUBLIC_NOPRODUCT').'</em></li></ul>';
  }
 }

 public function ThemeEndBody(){
  echo '<?php if($plxMotor->mode == "product" || strstr($plxMotor->template,"boutique") || $plxMotor->plxPlugins->aPlugins["'.$this->plug['name'].'"]->shortcodeactif ){ ?>';
//javascript de bascule des boutons produits
?>
<script type="text/javascript">function chngNbProd(e,t){var a=document.getElementById("addProd"+e),d=document.getElementById("nbProd"+e);"<?php echo $this->getLang('L_PUBLIC_ADD_BASKET'); ?>"!=a.value&&(d.value==d.getAttribute("data-o")||0==d.value?(t&&(d.value="0"),a.value="<?php echo $this->getLang('L_PUBLIC_DEL_BASKET'); ?>",a.setAttribute("class","red")):(a.value="<?php echo $this->getLang('L_PUBLIC_MOD_BASKET'); ?>",a.setAttribute("class","orange")))}</script>
<?php
  echo '<?php } ?>'; // fi if mode product || strstr template boutique || shrotcode
  if (isset($_SESSION[$this->plug['name']]["msgProdUpDate"]) && $_SESSION[$this->plug['name']]["msgProdUpDate"]){
   unset($_SESSION[$this->plug['name']]["msgProdUpDate"]);
//Les messages de MAJ panier
?>
<div id="msgUpDateCart"><?php $this->lang('L_PUBLIC_MSG_BASKET_UP'); ?></div>
<script type="text/javascript">
 var msgUpDateCart = document.getElementById("msgUpDateCart");
 msgUpDateCart.style.display = "block";
 setTimeout(function(){document.getElementById("msgUpDateCart").style.display = "none"; }, 3000);
 var shoppingCart = null;
</script>
<?php } // fi Les messages de MAJ panier
 }//end ThemeEndBody

 public function IndexEnd(){//MyshopCookie

  $string = '
  // MyShopCookie';
  if(isset($_SESSION[$this->plug['name']]["prods"])){

   // localhost pour test ou véritable domaine ?
   $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;

   // Durée de vie cookie = fin de session par défaut
   $temps_du_cookie = 0;

   // Durée de vie du cookie = 2 mois si au moins un produit dans le panier
   if (isset($_SESSION[$this->plug['name']]["ncart"]) && $_SESSION[$this->plug['name']]["ncart"]>0)
    $temps_du_cookie = time() + 3600 * 24 * 30 * 2;

   $string .= '
   if(isset($_SESSION["'.$this->plug['name'].'"])){
   $cookie_path = "/";
   $cookie_domain = "'.$domain.'";
   $cookie_secure = 0;
   $cookie_expire = '.$temps_du_cookie.';
   $cookie_value["prods"]=preg_replace("/[^0-9]/","",$_SESSION["'.$this->plug['name'].'"]["prods"]);
   $cookie_value["ncart"]=intval($_SESSION["'.$this->plug['name'].'"]["ncart"]);
   if (version_compare(PHP_VERSION, "5.2.0", ">="))
    setcookie("'.$this->plug['name'].'", json_encode($cookie_value), $cookie_expire, $cookie_path, $cookie_domain, $cookie_secure, true);
   else
    setcookie("'.$this->plug['name'].'", serialize($cookie_value), $cookie_expire, $cookie_path."; HttpOnly", $cookie_domain, $cookie_secure);
   }';
  }
  echo "<?php ".$string." ?>";
 }
 public function Index(){//MyshopCookie
  $string = '
  // MyShopCookie
  if(!empty($_COOKIE["'.$this->plug['name'].'"]) && !isset($_SESSION["IS_NOT_NEW"])) {
   if (version_compare(PHP_VERSION, "5.2.0", ">="))
    $cookie_value = json_decode($_COOKIE["'.$this->plug['name'].'"],true);
   else
    $cookie_value = unserialize($_COOKIE["'.$this->plug['name'].'"]);    
   $_SESSION["'.$this->plug['name'].'"]["prods"] = preg_replace("/[^0-9]/","",$cookie_value["prods"]);
   $_SESSION["'.$this->plug['name'].'"]["ncart"] = intval($cookie_value["ncart"]);
  }
  $_SESSION["IS_NOT_NEW"]=true;';
  echo "<?php ".$string." ?>";
 }
// hook des boutons localStorage du formulaire pour les clients au milieu du Panier
 public function inlineHtml(){//MyshopCookie ?>
    <p><span id="bouton_sauvegarder">&nbsp;</span>&nbsp;<span id="bouton_effacer">&nbsp;</span>&nbsp;<span id="bouton_raz">&nbsp;</span></p>
    <p id="alerte_sauvegarder" class="alert green" style="display:none;">&nbsp;</p>
<?php
}
// hook js localStorage du formulaire pour les clients à la fin du Panier
 public function inlineJs(){//MyshopCookie ?>
<script type="text/JavaScript">

 if (window.localStorage){
  function lsTest(){
   var test = "test";
   try {
    localStorage.setItem(test, test);
    localStorage.removeItem(test);
    return true;
   } catch(e) {
    return false;
   }
  }

  if(lsTest() === true){
   function stock(){
    document.getElementById("bouton_effacer").style.display = "";
    document.getElementById("bouton_sauvegarder").style.display = "none";
    var temp = {
    firstname:document.getElementById("firstname").value,
    lastname:document.getElementById("lastname").value,
    email:document.getElementById("email").value,
    tel:document.getElementById("tel").value,
    adress:document.getElementById("adress").value,
    postcode:document.getElementById("postcode").value,
    city:document.getElementById("city").value,
    country:document.getElementById("country").value,
    };
    localStorage.setItem("Shop_Deliver_Adress", JSON.stringify(temp));
    document.getElementById("alerte_sauvegarder").innerHTML = "<?php echo $this->lang('L_ADDRESS_SAVED'); ?><br /><?php echo $this->lang('L_DO_NOT_SHARED'); ?>";
    document.getElementById("alerte_sauvegarder").style.display = "block";
    setTimeout(function(){
    document.getElementById("alerte_sauvegarder").style.display = "none"; }, 3000);
   }
   function clear(){
    document.getElementById("bouton_effacer").style.display = "none";
    document.getElementById("bouton_sauvegarder").style.display = "";
    localStorage.removeItem("Shop_Deliver_Adress"); 
    document.getElementById("alerte_sauvegarder").innerHTML = "<?php echo $this->lang('L_ADDRESS_DELETED'); ?>";
    document.getElementById("alerte_sauvegarder").style.display = "block";
    setTimeout(function(){
    document.getElementById("alerte_sauvegarder").style.display = "none"; }, 3000);
   }
   function raz(){
    clear();
    document.getElementById("firstname").value = "";
    document.getElementById("lastname").value = "";
    document.getElementById("email").value = "";
    document.getElementById("tel").value = "";
    document.getElementById("adress").value = "";
    document.getElementById("postcode").value = "";
    document.getElementById("city").value = "";
    document.getElementById("country").value = "";
   }
   function detail(event){
    if (event.target.type == "text" || event.target.type == "email"){
     document.getElementById("bouton_effacer").style.display = "none";
     document.getElementById("bouton_sauvegarder").style.display = "";
    }
   }
   var gm = JSON.parse(localStorage.getItem("Shop_Deliver_Adress"));
   if (gm != null){
    document.getElementById("firstname").value = gm["firstname"];
    document.getElementById("lastname").value = gm["lastname"];
    document.getElementById("email").value = gm["email"];
    document.getElementById("tel").value = gm["tel"];
    document.getElementById("adress").value = gm["adress"];
    document.getElementById("postcode").value = gm["postcode"];
    document.getElementById("city").value = gm["city"];
    document.getElementById("country").value = gm["country"];
   }
   var bouton_un = document.getElementById("bouton_sauvegarder");
   var input_un = document.createElement("input");
   input_un.setAttribute("name","SaveAdress");
   input_un.setAttribute("value","<?php echo $this->lang('L_SAVE_MY_ADDRESS'); ?>");
   input_un.setAttribute("type","button");
   input_un.addEventListener("click",stock, false);

   var bouton_deux = document.getElementById("bouton_effacer");
   input_deux = document.createElement("input");
   input_deux.setAttribute("name","ClearAdress");
   input_deux.setAttribute("value","<?php echo $this->lang('L_DELETE_MY_ADDRESS'); ?>");
   input_deux.setAttribute("type","button");
   input_deux.addEventListener("click",clear, false);

   var bouton_raz = document.getElementById("bouton_raz");
   input_raz = document.createElement("input");
   input_raz.setAttribute("name","RAZAdresse");
   input_raz.setAttribute("value","<?php echo $this->lang('L_RESET_ADDRESS'); ?>");
   input_raz.setAttribute("type","button");
   input_raz.addEventListener("click",raz, false);

   var form_client = document.getElementById("formcart");
   form_client.addEventListener("change",detail, false);

   if (gm != null)
    bouton_un.style.display = "none";
   else
    bouton_deux.style.display = "none";

   bouton_un.appendChild(input_un);
   bouton_deux.appendChild(input_deux);
   bouton_raz.appendChild(input_raz);
  }
 }
</script>

<?php
 }

 public function productNumber(){
  return $this->idProduit;
 }

 /**
  * Méthode de traitement du hook plxShowConstruct
  * @return stdio
  * @author Stephane F
  **/
 public function plxShowConstruct(){
  if (isset($this->aProds[$this->productNumber()]['name'])){
   # infos sur la page statique
   $string  = "if(\$this->plxMotor->mode=='product'){";
   $string .= " \$array = array();";
   $string .= " \$array[\$this->plxMotor->cible] = array(
    'name'  => '" . self::nomProtege($this->aProds[$this->productNumber()]["name"]) . "',
    'menu'  => '',
    'url'  => '/../template/affichageProduitPublic',
    'readable' => 1,
    'active' => 1,
    'group'  => ''
   );";
   $string .= " \$this->plxMotor->aStats = array_merge(\$this->plxMotor->aStats, \$array);";
   $string .= "}";
   echo "<?php ".$string." ?>";
  }
 }

 public function AdminPrepend(){
  $this->plxMotor = plxAdmin::getInstance();

  if (isset($this->plxMotor->aConf['images'])){
   // jusqu'à la version 5.3.1
   $this->cheminImages = $this->plxMotor->aConf['images'];
  } else {
   $this->cheminImages = $this->plxMotor->aConf['medias'];
  }
 }

 /**
  * Méthode qui affiche un message si l'adresse email du contact n'est pas renseignée ou si la langue est absente
  *
  * @return stdio
  * @author Stephane F
  **/
 public function AdminTopBottom() {
  echo '<?php
  if($plxAdmin->plxPlugins->aPlugins["'.$this->plug['name'].'"]->getParam("email")=="") {
   echo "<p class=\"warning\">Plugin MyShop<br />'.$this->getLang("L_ERR_EMAIL").'</p>";
   plxMsg::Display();
  }

  $file = PLX_PLUGINS."plxMyShop/lang/".$plxAdmin->aConf["default_lang"].".php";
  if(!file_exists($file)) {
   echo "<p class=\"warning\">Plugin MyShop<br />".sprintf("'.$this->getLang('L_LANG_UNAVAILABLE').'", $file)."</p>";
   plxMsg::Display();
  }
  ?>';
 }

 public function plxMotorParseArticle() {// 4 shortcode in article [boutonPanier ###]
  echo "<?php";
?>
  if(get_class($this)=='plxMotor'){//only 4 public page!
   $plxPlugin = $this->plxPlugins->aPlugins['plxMyShop'];
   if(!empty($art['chapo']))
    $art['chapo'] = $plxPlugin->traitementPageStatique($art['chapo']);
   $art['content'] = $plxPlugin->traitementPageStatique($art['content']);
   unset($plxPlugin);
  }
  ?>
<?php
 }

 public function plxShowStaticContent(){
  echo "<?php";
?>
   $plxPlugin = $this->plxMotor->plxPlugins->aPlugins['plxMyShop'];
   $output = $plxPlugin->traitementPageStatique($output);
   unset($plxPlugin);
  ?>
<?php
 }

 public function traitementPageStatique($output){
  preg_match_all("!\\[{$this->shortcode} (.*)\\]!U", $output, $resultat);
  if (0 < count($resultat[1])){
   $this->shortcodeactif = true;
   $resultat[1] = array_unique($resultat[1]);
   $tabCodes = array();
   $tabRemplacement = array();

   $this->donneesModeles["plxPlugin"] = $this;

   foreach ($resultat[1] as $codeProduit){
    $tabCodes[] = "[{$this->shortcode} $codeProduit]";
    ob_start();
    $this->donneesModeles["k"] = $codeProduit;
    $this->modele("espacePublic/boucle/produitRubrique");
    $tabRemplacements[] = ob_get_clean();
   }
   $output = str_replace($tabCodes, $tabRemplacements, $output);
   ob_start();

   if (in_array(
     $this->getParam("affPanier")
     , array("basPage", "partout")
    )
   ){
    $_SESSION[$this->plug['name']]['msgCommand']="";
    $this->validerCommande();
    $this->modele("espacePublic/panier");
   }
     //~ else {
      //~ $this->modele("espacePublic/ajoutProduit");
     //~ }
   $output .= ob_get_clean();
  }
  return $output;
 }

 /**
  * Méthode qui effectue une analyse de la situation et détermine
  * le mode à appliquer. Cette méthode alimente ensuite les variables
  * de classe adéquates
  * @return null
  * @author Anthony GUÉRIN, Florent MONTHEL, Stéphane F
  **/
 public function plxMotorPreChauffageBegin(){
  $this->plxMotor = plxMotor::getInstance();
  
  # Hook Plugins
  eval($this->plxMotor->plxPlugins->callHook("plxMyShop_debut"));
  $this->cheminImages = $this->plxMotor->aConf['medias'];
  if (isset($this->plxMotor->aConf['images']))// jusqu'à la version 5.3.1
   $this->cheminImages = $this->plxMotor->aConf['images'];

  $nomPlugin = __CLASS__;

  // contrôleur des pages du plugin
  if (preg_match("/boutique\/panier/",$this->plxMotor->get)){
   $classeVue = "panier";

   require_once "classes/vues/$classeVue.php";
   $this->vue = new $classeVue();
   $this->vue->plxPlugin = $this;
   $this->vue->traitement();

   $this->plxMotor->mode = "static";
   $this->plxMotor->cible = $nomPlugin;
   $this->plxMotor->template = $this->getParam("template");

   $this->plxMotor->aConf["racine_statiques"] = "";
   $this->plxMotor->aStats[$this->plxMotor->cible] = array(
    "name" => $this->vue->titre(),
    "url" => "/../{$this->plxMotor->aConf["racine_plugins"]}$nomPlugin/template/vue",#maybe in old pluxml add slash "$nomPlugin/template/vue" ?
    "active" => 1,
    "menu" => "non",
    "readable" => 1,
    "title_htmltag" => "",
   );
   echo "<?php return TRUE;?>";
  }

  // pages des produits et des catégories
  if (preg_match("#product([0-9]+)/?([a-z0-9-]+)?#", $this->plxMotor->get, $resultat)){
   $this->idProduit = str_pad($resultat[1], 3, "0", STR_PAD_LEFT);

   $template = $this->aProds[$this->productNumber()]["template"] === ""
     ? $this->getParam('template')
     : $this->aProds[$this->productNumber()]["template"];

   $this->plxMotor->mode = "product";
   $this->plxMotor->aConf["racine_statiques"] = "";
   $this->plxMotor->cible = "{$this->plxMotor->aConf["racine_plugins"]}$nomPlugin/form";#maybe in old pluxml add slash "/$nomPlugin/form" ?
   $this->plxMotor->template = $template;
   echo "<?php return TRUE;?>";
  }
 }

 /**
  * Méthode qui renseigne le titre de la page dans la balise html <title>
  * @return stdio
  * @author Stephane F
  **/
 public function plxShowPageTitle(){
  if (isset($this->aProds[$this->productNumber()]['name'])){
   echo '<?php
    if($this->plxMotor->mode == "product"){
     echo plxUtils::strCheck($this->plxMotor->aConf["title"]  . \' - '
      . self::nomProtege($this->aProds[$this->productNumber()]["name"]) .'\');
     return true;
    }
   ?>';
  }
 }

 public static function nomProtege($nomProduit){
  return str_replace("\\\"", "\"", addslashes($nomProduit));
 }

 /**
  * Méthode qui référence les produits dans le sitemap
  * @return stdio
  * @author David.L
  **/
 public function SitemapStatics(){
  if (isset($this->aProds) && is_array($this->aProds)){
   foreach($this->aProds as $key => $value){
    if ($value['active']==1 &&  $value['readable']==1):
     echo '<?php
     echo "\n";
     echo "\t<url>\n";
     echo "\t\t<loc>".$plxMotor->urlRewrite("?'.$this->lang.'product'.$key.'/'.$value['url'].'")."</loc>\n";
     echo "\t\t<lastmod>'.date('Y-m-d').'</lastmod>\n";
     echo "\t\t<changefreq>daily</changefreq>\n";
     echo "\t\t<priority>0.8</priority>\n";
     echo "\t</url>\n";
     ?>';
    endif;
   }
  }
 }

 /**
  * Méthode qui parse le fichier les produits et alimente
  * le tableau aProds
  * @param filename emplacement du fichier XML des produits
  * @return null
  * @author David.L
  **/
 public function getProducts($filename=''){
  $filename = $filename=='' ? PLX_ROOT.PLX_CONFIG_PATH.'products.xml' : $filename;
  if(!is_file($filename)) return;

  # Mise en place du parseur XML
  $data = implode('',file($filename));
  $parser = xml_parser_create(PLX_CHARSET);
  xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
  xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
  xml_parse_into_struct($parser,$data,$values,$iTags);
  xml_parser_free($parser);
  if(isset($iTags['product']) AND isset($iTags['name'])){
   $nb = sizeof($iTags['name']);
   $size=ceil(sizeof($iTags['product'])/$nb);
   for($i=0;$i<$nb;$i++){
    $attributes = $values[$iTags['product'][$i*$size]]['attributes'];
    $number = $attributes['number'];

    # Recuperation du nom du produit
    $this->aProds[$number]['name']=plxUtils::getValue($values[$iTags['name'][$i]]['value']);

    # Recuperation prix ttc
    $pricettc = plxUtils::getValue($iTags['pricettc'][$i]);
    $this->aProds[$number]['pricettc']=plxUtils::getValue($values[$pricettc]['value']);

    # Recuperation noaddcart
    $noaddcart = plxUtils::getValue($iTags['noaddcart'][$i]);
    $this->aProds[$number]['noaddcart']=plxUtils::getValue($values[$noaddcart]['value']);
    $notice_noaddcart = plxUtils::getValue($iTags['notice_noaddcart'][$i]);
    $this->aProds[$number]['notice_noaddcart']=plxUtils::getValue($values[$notice_noaddcart]['value']);

    # Recuperation poid
    $poidg = plxUtils::getValue($iTags['poidg'][$i]);
    $this->aProds[$number]['poidg']=plxUtils::getValue($values[$poidg]['value']);

    # Recuperation image
    $image = plxUtils::getValue($iTags['image'][$i]);
    $this->aProds[$number]['image']=plxUtils::getValue($values[$image]['value']);

    # Recuperation de la balise title
    $title_htmltag = plxUtils::getValue($iTags['title_htmltag'][$i]);
    $this->aProds[$number]['title_htmltag']=plxUtils::getValue($values[$title_htmltag]['value']);

    # Recuperation du meta description
    $meta_description = plxUtils::getValue($iTags['meta_description'][$i]);
    $this->aProds[$number]['meta_description']=plxUtils::getValue($values[$meta_description]['value']);

    # Recuperation du meta keywords
    $meta_keywords = plxUtils::getValue($iTags['meta_keywords'][$i]);
    $this->aProds[$number]['meta_keywords']=plxUtils::getValue($values[$meta_keywords]['value']);

    # Recuperation du groupe du produit
    $this->aProds[$number]['group']=plxUtils::getValue($values[$iTags['group'][$i]]['value']);

    # Recuperation du de la variable categorie
    $this->aProds[$number]['pcat']=plxUtils::getValue($values[$iTags['pcat'][$i]]['value']);

    $this->aProds[$number]['menu']=plxUtils::getValue($values[$iTags['menu'][$i]]['value']);

    # Recuperation de l'url du produit
    $this->aProds[$number]['url']=strtolower($attributes['url']);

    # Recuperation de l'etat du produit
    $this->aProds[$number]['active']=intval($attributes['active']);

    # recuperation du fichier template
    $this->aProds[$number]['template']=isset($attributes['template'])?$attributes['template']:$this->getParam('template');

    # On verifie que le produit existe bien
    $file = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$number.'.'.$attributes['url'].'.php';

    # On test si le fichier est lisible
    $this->aProds[$number]['readable'] = (is_readable($file) ? 1 : 0);
   }
  }
 }

 /**
  * Méthode qui édite le fichier XML des produits selon le tableau $content
  * @param content tableau multidimensionnel des produits
  * @param action permet de forcer la mise àjour du fichier
  * @return string
  * @author David L.
  **/
 public function editProducts($content, $action=false){
  $save = $this->aProds;
  # suppression
  if(!empty($content['selection']) AND $content['selection']=='delete' AND isset($content['idProduct'])){
   foreach($content['idProduct'] as $product_id){
    $filename = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$product_id.'.'.$this->aProds[$product_id]['url'].'.php';
    if(is_file($filename)) unlink($filename);
    # si le produit supprimée est en page d'accueil on met à jour le parametre
    unset($this->aProds[$product_id]);
    $action = true;
   }
  }
  # mise à jour de la liste des produits
  elseif(!empty($content['update'])){
   foreach($content['productNum'] as $product_id){
    $stat_name = $content[$product_id.'_name'];
    if($stat_name!=''){
     $url = (isset($content[$product_id.'_url'])?trim($content[$product_id.'_url']):'');
     $stat_url = ($url!=''?plxUtils::title2url($url):plxUtils::title2url($stat_name));
     if($stat_url=='') $stat_url = L_DEFAULT_NEW_PRODUCT_URL;
     # On vérifie si on a besoin de renommer le fichier du produit
     if(isset($this->aProds[$product_id]) AND $this->aProds[$product_id]['url']!=$stat_url){
      $oldfilename = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$product_id.'.'.$this->aProds[$product_id]['url'].'.php';
      $newfilename = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$product_id.'.'.$stat_url.'.php';
      if(is_file($oldfilename)) rename($oldfilename, $newfilename);
     }
     $this->aProds[$product_id]['pcat'] = trim($content[$product_id.'_pcat']);
     $this->aProds[$product_id]['menu'] = trim($content[$product_id.'_menu']);
     $this->aProds[$product_id]['group'] = (isset($this->aProds[$product_id]['group'])?$this->aProds[$product_id]['group']:'');
     $this->aProds[$product_id]['name'] = $stat_name;
     $this->aProds[$product_id]['url'] = plxUtils::checkSite($url)?$url:$stat_url;
     $this->aProds[$product_id]['active'] = $content[$product_id.'_active'];
     $this->aProds[$product_id]['ordre'] = intval($content[$product_id.'_ordre']);
     $this->aProds[$product_id]['template'] = (isset($this->aProds[$product_id]['template'])?$this->aProds[$product_id]['template']:$this->getParam('template'));
     $this->aProds[$product_id]['title_htmltag'] = (isset($this->aProds[$product_id]['title_htmltag'])?$this->aProds[$product_id]['title_htmltag']:'');
     $this->aProds[$product_id]['image'] = (isset($this->aProds[$product_id]['image'])?$this->aProds[$product_id]['image']:'');
     $this->aProds[$product_id]['noaddcart'] = (isset($this->aProds[$product_id]['noaddcart'])?$this->aProds[$product_id]['noaddcart']:'');
     $this->aProds[$product_id]['notice_noaddcart'] = (isset($this->aProds[$product_id]['notice_noaddcart'])?$this->aProds[$product_id]['notice_noaddcart']:'');
     $this->aProds[$product_id]['pricettc'] = (isset($this->aProds[$product_id]['pricettc'])?$this->aProds[$product_id]['pricettc']:'');
     $this->aProds[$product_id]['poidg'] = (isset($this->aProds[$product_id]['poidg'])?$this->aProds[$product_id]['poidg']:'');
     $this->aProds[$product_id]['meta_description'] = (isset($this->aProds[$product_id]['meta_description'])?$this->aProds[$product_id]['meta_description']:'');
     $this->aProds[$product_id]['meta_keywords'] = (isset($this->aProds[$product_id]['meta_keywords'])?$this->aProds[$product_id]['meta_keywords']:'');
     $action = true;
    }
   }
   # On va trier les clés selon l'ordre choisi
   if(sizeof($this->aProds)>0) uasort($this->aProds, create_function('$a, $b', 'return $a["ordre"]>$b["ordre"];'));
  }
  # sauvegarde
  if($action){
   $products_name = array();
   $products_url = array();
   # On génére le fichier XML
   $xml = "<?xml version=\"1.0\" encoding=\"".PLX_CHARSET."\"?>\n";
   $xml .= "<document>\n";
   if (isset($this->aProds) && is_array($this->aProds)){
    foreach($this->aProds as $product_id => $product){

     # control de l'unicité du titre de la page
     if(in_array($product['name'], $products_name))
      return plxMsg::Error(L_ERR_PRODUCT_ALREADY_EXISTS.' : '.plxUtils::strCheck($product['name']));
     else
      $products_name[] = $product['name'];

     # control de l'unicité de l'url de la page
     if(in_array($product['url'], $products_url)){
      $this->aProds = $save;
      return plxMsg::Error(L_ERR_URL_ALREADY_EXISTS.' : '.plxUtils::strCheck($product['url']));
     }
     else
      $products_url[] = $product['url'];
     $xml .= "\t<product number=\"".$product_id."\" active=\"".$product['active']."\" url=\"".$product['url']."\" template=\"".basename($product['template'])."\">";
     $xml .= "<pcat><![CDATA[".plxUtils::cdataCheck($product['pcat'])."]]></pcat>";
     $xml .= "<menu><![CDATA[".plxUtils::cdataCheck($product['menu'])."]]></menu>";
     $xml .= "<group><![CDATA[".plxUtils::cdataCheck($product['group'])."]]></group>";
     $xml .= "<name><![CDATA[".plxUtils::cdataCheck($product['name'])."]]></name>";
     $xml .= "<image><![CDATA[".plxUtils::cdataCheck($product['image'])."]]></image>";
     $xml .= "<noaddcart><![CDATA[".plxUtils::cdataCheck($product['noaddcart'])."]]></noaddcart>";
     $xml .= "<notice_noaddcart><![CDATA[".plxUtils::cdataCheck($product['notice_noaddcart'])."]]></notice_noaddcart>";
     $xml .= "<pricettc><![CDATA[".plxUtils::cdataCheck($product['pricettc'])."]]></pricettc>";
     $xml .= "<poidg><![CDATA[".plxUtils::cdataCheck(($product['poidg']==0?"0.0":$product['poidg']))."]]></poidg>";
     $xml .= "<meta_description><![CDATA[".plxUtils::cdataCheck($product['meta_description'])."]]></meta_description>";
     $xml .= "<meta_keywords><![CDATA[".plxUtils::cdataCheck($product['meta_keywords'])."]]></meta_keywords>";
     $xml .= "<title_htmltag><![CDATA[".plxUtils::cdataCheck($product['title_htmltag'])."]]></title_htmltag>";
     # Hook plugins
     //eval($this->plxPlugins->callHook('plxAdminEditProductsXml'));
     $xml .= "</product>\n";
    }
   }
   $xml .= "</document>";
   # On écrit le fichier si une action valide a été faite
   if(plxUtils::write($xml,PLX_ROOT.PLX_CONFIG_PATH.'products.xml')){
    return plxMsg::Info(L_SAVE_SUCCESSFUL);
   } else {
    $this->aProds = $save;
    return plxMsg::Error(L_SAVE_ERR.' '.PLX_ROOT.PLX_CONFIG_PATH.'products.xml');
   }
  }
 }
 
 /**
  * Méthode qui lit le fichier d'un produit
  * @param num numero du fichier du produit
  * @return string contenu de la page
  * @author Stephane F.
  **/
 public function getFileProduct($num){
  # Emplacement de la page
  $filename = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$num.'.'.$this->aProds[ $num ]['url'].'.php';
  if(is_file($filename) AND filesize($filename) > 0){
   if($f = fopen($filename, 'r')){
    $content = fread($f, filesize($filename));
    fclose($f);
    # On retourne le contenu
    return $content;
   }
  }
  return null;
 }

 /**
  * Méthode qui sauvegarde le contenu d'un produit
  * @param content données à sauvegarder
  * @return string
  * @author David.L
  **/
 public function editProduct($content){
  # Mise à jour du fichier product.xml
  if (isset($content["listeCategories"])){
   $this->aProds[$content['id']]['group'] = implode(",", $content["listeCategories"]);
  }

  // formatage du prix et du poids à l'édition
  foreach (array("pricettc", "poidg") as $champ){
   $content[$champ] = number_format($content[$champ], 2, ".", "");
  }

  // données du produit
  $this->aProds[$content['id']]['image'] = $content['image'];
  $this->aProds[$content['id']]['noaddcart'] = $content['noaddcart'];
  $this->aProds[$content['id']]['notice_noaddcart'] = $content['notice_noaddcart'];
  $this->aProds[$content['id']]['pricettc'] = $content['pricettc'];
  $this->aProds[$content['id']]['poidg'] = $content['poidg'];
  $this->aProds[$content['id']]['template'] = $content['template'];
  $this->aProds[$content['id']]['title_htmltag'] = trim($content['title_htmltag']);
  $this->aProds[$content['id']]['meta_description'] = trim($content['meta_description']);
  $this->aProds[$content['id']]['meta_keywords'] = trim($content['meta_keywords']);
  # Hook plugins
  //eval($this->plxPlugins->callHook('plxAdminEditProduct'));

  if($this->editProducts(null,true)){
   if (!is_dir(PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')))){
    mkdir(PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')), 0755, true);
   }
   # Génération du nom du fichier de la page statique
   $filename = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$content['id'].'.'.$this->aProds[ $content['id'] ]['url'].'.php';
   # On écrit le fichier
   if(plxUtils::write($content['content'],$filename))
    return plxMsg::Info(L_SAVE_SUCCESSFUL);
   else
    return plxMsg::Error(L_SAVE_ERR.' '.$filename);
  }
 }

 /**
  * Méthode qui retourne l'id du produit active
  * @return int
  * @scope product
  * @author David.L
  **/
 public function productId(){
  # On va verifier que la categorie existe en mode categorie
  if($this->mode == 'product' AND isset($this->aProds[ $this->productNumber() ]))
   return intval($this->productNumber());
 }

 /**
  * Méthode qui affiche l'url du produit de type relatif ou absolu
  * @param type type de lien : relatif ou absolu (URL complète)
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productUrl($type='relatif'){
  # Recupération ID URL
  $productId = $this->productId();
  $productIdFill = str_pad($productId,3,'0',STR_PAD_LEFT);
  if(!empty($productId) AND isset($this->aProds[ $productIdFill ]))
   echo $this->urlRewrite('?'.$this->lang.'product'.$productId.'/'.$this->aProds[ $productIdFill ]['url']);
 }

 public function productRUrl($key){
  # Recupération ID URL
  $productId = intval($key);
  if(!empty($productId) AND isset($this->aProds[$key]))
   return $this->plxMotor->urlRewrite('?'.$this->lang.'product'.$productId.'/'.$this->aProds[$key]['url']);
 }

 /**
  * Méthode qui affiche le titre du produit
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productTitle(){
  echo plxUtils::strCheck(
   preg_replace(
    "/'/"
    , '&apos;'
    , $this->aProds[$this->productNumber()]['name']
   )
  );
 }
 
 /**
  * Méthode qui affiche l'image du produit
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productImage(){
  return plxUtils::strCheck(
   $this->plxMotor->urlRewrite(
    $this->cheminImages
    . $this->aProds[$this->productNumber()]["image"]
   )
  );
 }

 /**
  * Méthode qui affiche le prix TTC du produit
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productPriceTTC(){

  #echo plxUtils::strCheck($this->aProds[ $this->productNumber() ]['pricettc']);
  return plxUtils::strCheck($this->aProds[ $this->productNumber() ]['pricettc']);
 }

 /**
  * Méthode qui affiche ou pas le bouton acheter
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productNoAddCart(){
  return plxUtils::strCheck($this->aProds[ $this->productNumber() ]['noaddcart']);
 }

 /**
  * Méthode qui affiche une notice si le bouton ajouter au panier n'est pas afficher
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productNoticeNoAddCart(){
  return plxUtils::strCheck($this->aProds[ $this->productNumber() ]['notice_noaddcart']);
 }

 /**
  * Méthode qui affiche le poid en gramme du produit
  *
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productPoidG(){
  return plxUtils::strCheck($this->aProds[ $this->productNumber() ]['poidg']);
 }

 /**
  * Méthode qui affiche le groupe du produit
  *
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productGroup(){
  echo plxUtils::strCheck($this->aProds[ $this->productNumber() ]['group']);
 }
 
 /**
  * Méthode qui affiche le titre du groupe du produit
  *
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productGroupTitle(){
  if ($gt=explode(',',$this->aProds[ $this->productNumber() ]['group'])){
   $tmp=array();
   foreach($gt as $gTitle){
    $tmp[$gTitle]=$this->aProds[$gTitle]['name'];
   }
   return $tmp;
  } else return plxUtils::strCheck($this->aProds[$this->aProds[ $this->productNumber() ]['group']]['name']);
 }

 /**
  * Méthode qui affiche la date de la dernière modification du produit selon le format choisi
  *
  * @param format format du texte de la date (variable: #minute, #hour, #day, #month, #num_day, #num_month, #num_year(4), #num_year(2))
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function productDate($format='#day #num_day #month #num_year(4)'){

  # On genere le nom du fichier dont on veux récupérer la date
  $file = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$this->productNumber();
  $file .= '.'.$this->aProds[$this->productNumber() ]['url'].'.php';
  # Test de l'existence du fichier
  if(!is_file($file)) return;
  # On récupère la date de la dernière modification du fichier qu'on formate
  echo plxDate::formatDate(date('YmdHi', filemtime($file)), $format);
 }

 /**
  * Méthode qui inclut le code source du produit
  *
  * @return stdout
  * @scope product
  * @author David.L
  **/
 public function plxShowProductContent(){
  # On va verifier que la page a inclure est lisible
  if($this->aProds[ $this->productNumber() ]['readable'] == 1){
   # On genere le nom du fichier a inclure
   $file = PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$this->productNumber();
   $file .= '.'.$this->aProds[ $this->productNumber() ]['url'].'.php';
   # Inclusion du fichier
   ob_start();
   require $file;
   $output = ob_get_clean();
   echo $output;
  } else {
   echo '<p>'.L_STATICCONTENT_INPROCESS.'</p>';
  }
 }

 /**
  * Méthode qui affiche un produit en lui passant son id (si ce produit est active ou non)
  *
  * @param id numérique de la page product
  * @return stdout
  * @scope global
  * @author David.L
  **/
 public function plxShowProductInclude($id){
  # Hook Plugins
  //if(eval($this->plxMotor->plxPlugins->callHook('plxShowProductInclude'))) return ;
  # On génère un nouvel objet plxGlob
  $plxGlob_stats = plxGlob::getInstance(PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')));
  if($files = $plxGlob_stats->query('/^'.str_pad($id,3,'0',STR_PAD_LEFT).'.[a-z0-9-]+.php$/')){
   include(PLX_ROOT.(empty($this->getParam('racine_products'))?'data/products/':$this->getParam('racine_products')).$files[0]);
  }
 }

 /**
  * Méthode de traitement du hook plxShowStaticListEnd
  *
  * @return stdio
  * @author David.L
  **/
 public function plxShowStaticListEnd(){
  $positionMenu = $this->getParam('menu_position') - 1;
  if (in_array(
    $this->getParam("affPanier")
    , array("pageSeparee", "partout")
   )
  ){
   // ajout du lien vers le panier
   $nomPlugin = __CLASS__;
   $panierSelectionne = (
     ("static" === $this->plxMotor->mode)
    && ($nomPlugin === $this->plxMotor->cible)
    && ("panier" === get_class($this->vue))
   );

   $classeCss = $panierSelectionne ? "active" : "noactive";
   $lienPanier = $this->plxMotor->urlRewrite('?'.$this->lang.'boutique/panier');

   require_once "classes/vues/panier.php";
   $vuePanier = new panier();
   $vuePanier->plxPlugin = $this;
   $titreProtege = plxMyShop::nomProtege($vuePanier->titre());

   // Afficher la page panier dans le menu ?
   if ($this->getParam("affichePanierMenu")!="non") {
    echo "<?php";
    echo " array_splice(\$menus, $positionMenu, 0";
    echo "  , '<li><a class=\"static $classeCss\" href=\"$lienPanier\" title=\"' . htmlspecialchars('$titreProtege') . '\">$titreProtege</a></li>'";
    echo " );";
    echo "?>";
   }
  }

  if ("pageSeparee" !== $this->getParam("affPanier")){
   $lienPanier = $this->plxMotor->urlRewrite("#panier");
  }

  $this->donneesModeles["lienPanier"] = $lienPanier;

  # ajout du menu pour accèder aux rubriques
  if (isset($this->aProds)
   &&is_array($this->aProds)
   &&("non" !== $this->getParam('afficheCategoriesMenu'))
  ){
   foreach(array_reverse($this->aProds) as $k=>$v){
    if ($v['menu']!='non' && $v['menu']!=''){
     $nomProtege = self::nomProtege($v['name']);
     $categorieSelectionnee = (
       ("product" === $this->plxMotor->mode)
      && ("product$k/{$v["url"]}" === $this->plxMotor->get)
     );

     $classeCss = $categorieSelectionnee ? "active" : "noactive";
     $lien = $this->plxMotor->urlRewrite('?'.$this->lang."product$k/{$v["url"]}");

     echo "<?php";
     echo " array_splice(\$menus, $positionMenu, 0";
     echo "  , '<li><a class=\"static $classeCss\" href=\"$lien\" title=\"' . htmlspecialchars('$nomProtege') . '\">$nomProtege</a></li>'";
     echo " );";
     echo "?>";
    }
   }
  } // FIN if ajout du menu pour accèder aux rubriques
 } // FIN public function plxShowStaticListEnd(){

 public function modele($modele){

  if (!isset($this->donneesModeles["pileModeles"])){
   $this->donneesModeles["pileModeles"] = array();
  }

  $this->donneesModeles["pileModeles"][] = $modele;

  // fichier du modèle dans le thème
  $plxMotor = plxMotor::getInstance();
  $racineTheme = PLX_ROOT . $plxMotor->aConf["racine_themes"] . $plxMotor->style;
  $fichier = "$racineTheme/modeles/plxMyShop/$modele.php";

  // si le fichier du modèle n'existe pas dans le thème
  if (!is_file($fichier)){
   // on choisi le fichier par défaut dans le répertoire de l'extension
   $fichier = "modeles/$modele.php";
  }

  $d = $this->donneesModeles;

  require $fichier;

  // rétablissement des noms des modèles
  array_pop($this->donneesModeles["pileModeles"]);
 }

 public function validerCommande(){
  if ( isset($_POST["methodpayment"])
   && !isset($this->donneesModeles["tabChoixMethodespaiement"][$_POST["methodpayment"]])
  ){
   // si la méthode de paiement n'est pas autorisé, choix par défaut
   $_POST["methodpayment"] = current($this->donneesModeles["tabChoixMethodespaiement"][$_POST["methodpayment"]]);
  }
  $msgCommand="";

  $TONMAIL=$this->getParam('email');
  $TON2EMEMAIL=$this->getParam('email_cc');
  $SHOPNAME=$this->getParam('shop_name');
  $COMMERCANTNAME=$this->getParam('commercant_name');
  $COMMERCANTPOSTCODE=$this->getParam('commercant_postcode');
  $COMMERCANTCITY=$this->getParam('commercant_city');
  $COMMERCANTSTREET=$this->getParam('commercant_street');

  //récupération de la liste des produit du panier
  $totalpricettc = 0;
  $totalpoidg = 0;
  $totalpoidgshipping = 0;
  $productscart = array();

  if( !isset($_SESSION[$this->plug['name']]['prods'])
   || (0 === count($_SESSION[$this->plug['name']]['prods']))
   || !isset($_POST["validerCommande"])
  ){
   return;
  }

  foreach ($_SESSION[$this->plug['name']]['prods'] as $idP => $nb){
   $productscart[$idP] = array(
    'name' => $this->aProds[$idP]['name'],
    'pricettc' => $this->aProds[$idP]['pricettc'] * $nb,
    'poidg' => $this->aProds[$idP]['poidg'] * $nb,
    'nombre' => $nb,
   );
   $totalpricettc += $productscart[$idP]["pricettc"];
   $totalpoidg += $productscart[$idP]["poidg"];
  }

  $totalpoidgshipping = $this->shippingMethod($totalpoidg, 1);

  #Mail de nouvelle commande pour le commerçant.
  $sujet = $this->getlang('L_EMAIL_SUBJECT').$SHOPNAME;
  $message = plxUtils::cdataCheck($_POST['firstname'])." ".plxUtils::cdataCheck($_POST['lastname'])."<br/>".
  plxUtils::cdataCheck($_POST['adress'])."<br/>".
  plxUtils::cdataCheck($_POST['postcode'])." ".plxUtils::cdataCheck($_POST['city'])."<br/>".
  plxUtils::cdataCheck($_POST['country'])."<br/>".
  $this->getlang('L_EMAIL_TEL').
  plxUtils::cdataCheck($_POST['tel'])."<br/><br/>".
  (!isset($_POST["choixCadeau"]) 
   ? $this->getlang('L_EMAIL_NOGIFT') 
   : $this->getlang('L_EMAIL_GIFT_FOR')." <strong>".htmlspecialchars($_POST["nomCadeau"]) . "</strong>."
  )
  ."<br/><br/>".
  $this->getlang('L_PAIEMENT').": ".($_POST['methodpayment']=="paypal"?$this->getlang('L_PAYMENT_PAYPAL'):$this->getlang('L_PAYMENT_CHEQUE')).
  "<br/>".$this->getlang('L_EMAIL_PRODUCTLIST')." :<br/><ul>";
  foreach ($productscart as $k => $v){
   $message.="<li>{$v['nombre']} × ".$v['name']."&nbsp;: ".$this->pos_devise($v['pricettc']). ((float)$v['poidg']>0?" ". $this->getlang('L_FOR')." " .$v['poidg']."&nbsp;kg":"")."</li>";
  }
  $message .= "</ul>";
  $message .= "<br/><br/>";
  $message .= "<em><strong>". $this->getlang('L_EMAIL_DELIVERY_COST'). " : ".$this->pos_devise($totalpoidgshipping). "</strong>";
  $message .= "<br/>";
  $message .= "<strong>".$this->getlang('L_EMAIL_WEIGHT')." : ".$totalpoidg."&nbsp;kg</strong></em>";
  $message .= "<br/>";
  $message .= "<strong>" . $this->getlang('L_TOTAL_BASKET')." ".$this->pos_devise(($totalpricettc+$totalpoidgshipping)). "</strong>";
  $message .= "<br/><br/>";
  $message .= $this->getlang('L_EMAIL_COMMENT')." : ";
  $message .= "<br/>";
  $message .= $_POST['msg'];

  $destinataire = $TONMAIL.(isset($TON2EMEMAIL) && !empty($TON2EMEMAIL)?', '.$TON2EMEMAIL:"");

  $headers  = "From: \"".plxUtils::cdataCheck($_POST['firstname'])." ".plxUtils::cdataCheck($_POST['lastname'])."\" <".$_POST['email'].">\r\n";
  $headers .= "Reply-To: ".$_POST['email']."\r\n";
  $headers .= "Content-Type: text/html;charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

  if ( (isset($_POST['email']) && $_POST['email']!="")
   && (isset($_POST['firstname']) && plxUtils::cdataCheck($_POST['firstname'])!="")
   && (isset($_POST['lastname']) &&  plxUtils::cdataCheck($_POST['lastname'])!="")
   && (isset($_POST['adress']) &&  plxUtils::cdataCheck($_POST['adress'])!="")
   && (isset($_POST['postcode']) &&  plxUtils::cdataCheck($_POST['postcode'])!="")
   && (isset($_POST['city']) && plxUtils::cdataCheck($_POST['city'])!="")
   && (isset($_POST['country']) && plxUtils::cdataCheck($_POST['country'])!="")
   && (!isset($_POST['choixCadeau']) || plxUtils::cdataCheck($_POST['nomCadeau'])!="")
   && (
    ("" === $this->getParam("urlCGV"))
    ||
    (  ("" !== $this->getParam("urlCGV"))
     && isset($_POST["valideCGV"])
    )
   )
  ){
   if(mail($destinataire,$sujet,$message,$headers)){
    $msgCommand.= "<h5 class='msgyeah' >".$this->getlang('L_EMAIL_CONFIRM_'.strtoupper($_POST['methodpayment']))."</h5>";
    #Mail de récapitulatif de commande pour le client.
    switch ($_POST['methodpayment']){
       case 'cheque' :
      $status = $this->getlang('L_WAITING'); 
      $method = $this->getlang('L_PAYMENT_CHEQUE');
      break; 
       case 'cash':
        $status = $this->getlang('L_WAITING'); 
        $method = $this->getlang('L_PAYMENT_CASH');
      break;
       case 'paypal':
        $status = $this->getlang('L_ONGOING'); 
        $method = $this->getlang('L_PAYMENT_PAYPAL');
      break;
       default:
      echo 'A method of payment is required!'; 
    } 
    $sujet = $this->getlang('L_EMAIL_CUST_SUBJECT') . $SHOPNAME;
    $message = "<p>" . $this->getlang('L_EMAIL_CUST_MESSAGE1') . " <a href='http://".$_SERVER["HTTP_HOST"]."'>".$SHOPNAME."</a><br/>".
     $this->getlang('L_EMAIL_CUST_MESSAGE2')." ". $status ." ".$this->getlang('L_EMAIL_CUST_MESSAGE3')."</p>";
    if ($_POST['methodpayment']=="cheque"){
     $message .="<p>". $this->getlang('L_EMAIL_CUST_CHEQUE') ." : ".$COMMERCANTNAME."<br/>". $this->getlang('L_EMAIL_CUST_SENDCHEQUE') ." :".
     "<br/><em>&nbsp;&nbsp;&nbsp;&nbsp;".$SHOPNAME."".
     "<br/>&nbsp;&nbsp;&nbsp;&nbsp;".$COMMERCANTNAME."".
     "<br/>&nbsp;&nbsp;&nbsp;&nbsp;".$COMMERCANTSTREET."".
     "<br/>&nbsp;&nbsp;&nbsp;&nbsp;".$COMMERCANTPOSTCODE." ".$COMMERCANTCITY."</em></p>";
    } elseif ($_POST['methodpayment']=="cash"){
      $message .="<p>".$this->getlang('L_EMAIL_CUST_CASH')."</p>";
    } elseif ($_POST['methodpayment']=="paypal"){
      $message .="<p>".$this->getlang('L_EMAIL_CUST_PAYPAL')."</p>";
    }
    $message .= "<br/><h1><u>".$this->getlang('L_EMAIL_CUST_SUMMARY')." :</u></h1>".
    "<br/><strong>".$this->getlang('L_EMAIL_CUST_ADDRESS')." :</strong><br/>".plxUtils::cdataCheck($_POST['firstname'])." ".plxUtils::cdataCheck($_POST['lastname'])."<br/>".
    plxUtils::cdataCheck($_POST['adress'])."<br/>".
    plxUtils::cdataCheck($_POST['postcode'])." ".plxUtils::cdataCheck($_POST['city'])."<br/>".
    plxUtils::cdataCheck($_POST['country'])."<br/>".
    "<strong>Tel : </strong>".plxUtils::cdataCheck($_POST['tel'])
    . "<br/><br/>" .
    (!isset($_POST["choixCadeau"]) 
     ? $this->getlang('L_EMAIL_NOGIFT') 
     : $this->getlang('L_EMAIL_GIFT_FOR') . " <strong>" . htmlspecialchars($_POST["nomCadeau"]) . "</strong>."
    )
    . "<br/><br/>" .
    "<strong>" . $this->getlang('L_EMAIL_CUST_PAYMENT') . ": </strong>". $method .
    "<br/><strong>" . $this->getlang('L_EMAIL_PRODUCTLIST') . " :</strong><br/>";
    foreach ($productscart as $k => $v){
     $message.="<li>{$v['nombre']} × ".$v['name']."&nbsp;: ".$this->pos_devise($v['pricettc']). ((float)$v['poidg']>0?" ".$this->getlang('L_FOR')." ".$v['poidg']."&nbsp;kg":"")."</li>";
    }
    $message.= "<br/><br/>".
    "<strong>". $this->getlang('L_TOTAL_BASKET') ." : </strong>".$this->pos_devise(($totalpricettc+$totalpoidgshipping)). "<br/>".
    "<em><strong>". $this->getlang('L_EMAIL_DELIVERY_COST') ." : </strong>".$this->pos_devise($totalpoidgshipping)."<br/>".
    "<strong>". $this->getlang('L_EMAIL_WEIGHT') ." : </strong>".$totalpoidg."&nbsp;kg<br/><br/></em>".
    "<strong>". $this->getlang('L_EMAIL_COMMENT') ." : </strong><br/>".plxUtils::cdataCheck($_POST['msg']);
 
    $destinataire = $_POST['email'];
    $headers  = "From: \"".$SHOPNAME."\" <".$TONMAIL.">\r\n";
    $headers .= "Reply-To: ".$TONMAIL.(isset($TON2EMEMAIL) && !empty($TON2EMEMAIL)?', '.$TON2EMEMAIL:"")."\r\n";
    $headers .= "Content-Type: text/html;charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

    if(mail($destinataire,$sujet,$message,$headers)){
     $msgCommand .= "<h5 class='msgyeah2'>". $this->getlang('L_EMAIL_SENT1') . "</h5>";
     $msgCommand .= "<h5 class='msgyeah3'>" . sprintf($this->getlang('L_EMAIL_SENT2'), $TONMAIL) . "</h5>";

     if ($_POST['methodpayment'] === "paypal"){
      $plxPlugin = $this;
      //require PLX_PLUGINS . 'plxMyShop/classes/paypal_api/SetExpressCheckout.php';
      require PLX_PLUGINS . 'plxMyShop/classes/paypal_api/boutonPaypalSimple.php';
     }

     $nf=PLX_ROOT.(empty(trim($_POST['racine_commandes']))?'data/commandes/':trim($_POST(['racine_commandes']))).date("Y-m-d_H-i-s_").$_POST['methodpayment'].'_'.$totalpricettc.'_'.$totalpoidgshipping.'.html';
     $monfichier = fopen($nf, 'w+');
     $commandeContent="<!DOCTYPE html>
<html>
<head>
<title>".$this->getlang('L_FILE_ORDER').date("d m Y")."</title>
<meta charset=\"UTF-8\">
<meta name=\"description\" content=\"Commande\">
<meta name=\"author\" content=\"plxMyShop\">
</head>
<body>
$message
<hr>
<a href=\"mailto:$destinataire\">$destinataire</a>
</body>
</html>";
     fputs($monfichier, $commandeContent);
     fclose($monfichier);
     chmod($nf, 0644);
     unset($_SESSION[$this->plug['name']]['prods']);
     unset($_SESSION[$this->plug['name']]['ncart']);
    }else{
     $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_EMAIL_ERROR1') ."</h5>";
    }
   }else{
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_EMAIL_ERROR2') ."</h5>";
    echo "<script type='text/javascript'>error=true;</script>";
   }
  } else {
   if ( (!isset($_POST['email']) || empty($_POST['email']) || $_POST['email']=="") ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_EMAIL') ."</h5>";
   }
   if (  (!isset($_POST['firstname']) ||  plxUtils::cdataCheck($_POST['firstname'])=="") ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_FIRSTNAME') ."</h5>";
   }
   if ( (!isset($_POST['lastname']) ||  plxUtils::cdataCheck($_POST['lastname'])=="")  ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_LASTNAME')  ."</h5>";
   }
   if ( (!isset($_POST['adress']) ||  plxUtils::cdataCheck($_POST['adress'])=="")  ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_ADDRESS') ."</h5>";
   }
   if ( (!isset($_POST['postcode']) ||  plxUtils::cdataCheck($_POST['postcode'])=="") ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_ZIP')  ."</h5>";
   }
   if ( (!isset($_POST['city']) ||  plxUtils::cdataCheck($_POST['city'])=="") ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_TOWN') ."</h5>";
   }
   if ( (!isset($_POST['country']) ||  plxUtils::cdataCheck($_POST['country'])=="") ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_COUNTRY') ."</h5>";
   }
   if ( (isset($_POST['choixCadeau']) &&  plxUtils::cdataCheck($_POST['nomCadeau']) === "") ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_GIFTNAME') ."</h5>";
   }
   if ( ("" !== $this->getParam("urlCGV")) && !isset($_POST["valideCGV"]) ){
    $msgCommand.= "<h5 class='msgerror'>". $this->getlang('L_MISSING_VALIDATION_CGV') ."</h5>";
   }
   echo "<script type='text/javascript'>error=true;</script>";
  }

  $_SESSION[$this->plug['name']]['msgCommand'] = $msgCommand;
  $_SESSION[$this->plug['name']]['methodpayment'] = $_POST["methodpayment"];

 } // FIN public function validerCommande(){

 //will position the price based on the config, before or after the price
 public function pos_devise($price){
  $price = number_format(
     $price
   , $this->getlang("L_NOMBRE_DECIMALES")
   , $this->getlang("L_POINT_DECIMAL")
   , $this->getlang("L_SEPARATEUR_MILLIERS")
  );
  if ( $this->getParam('position_devise') == "before" ){
   $pos_price = trim($this->getParam('devise')).'&nbsp;'.$price;
  } elseif ( $this->getParam('position_devise') == "after" ){
   $pos_price = $price.'&nbsp;'.trim($this->getParam('devise'));
  }
  return $pos_price;
 }

 public function shippingMethod($kg, $op){
  $shippingPrice=0.00;
  if($this->getParam("shipping_colissimo")=='0'){
   return (float) $shippingPrice;
  }
  $accurecept = (float) $this->getParam('acurecept');
  if ($kg<=0){
   $shippingPrice=$accurecept;
  }else{
   for($i=1;$i<=11;$i++){ 
    $num=str_pad($i, 2, "0", STR_PAD_LEFT);
    if ((float)$kg<=(float)$this->getParam('p'.$num)){
     $shippingPrice=((float)$this->getParam('pv'.$num)+$accurecept);
     break;
    }
   } 
  }
  return (float) $shippingPrice;
 }

 public function menuAdmin($ongletEnCours){
  $listeOnglets = [
   "produits" => [
    "titre" => $this->getLang("L_MENU_PRODUCTS"),
    "urlHtml" => "plugin.php?p=plxMyShop",
   ],
   "categories" => [
    "titre" => $this->getLang("L_MENU_CATS"),
    "urlHtml" => "plugin.php?p=plxMyShop&amp;mod=cat",
   ],
   "commandes" => [
    "titre" => $this->getLang("L_MENU_ORDERS"),
    "urlHtml" => "plugin.php?p=plxMyShop&amp;mod=cmd",
   ],
   "configuration" => [
    "titre" => $this->getLang("L_MENU_CONFIG"),
    "urlHtml" => "parametres_plugin.php?p=plxMyShop",
   ],
  ];
  foreach ($listeOnglets as $codeOnglet => $o){
?>
   <a href="<?php echo $o["urlHtml"];?>"><button<?php echo ($codeOnglet !== $ongletEnCours) ? "" : " disabled";?>><?php echo plxUtils::strCheck($o["titre"]);?></button></a>
<?php
  }
 }

 public function traitementAjoutPanier(){
  if (!isset($_POST["ajouterProduit"])) return;
  if (!isset($_SESSION)) session_start();
  if (!isset($_SESSION[$this->plug['name']]['prods'])) $_SESSION[$this->plug['name']]['prods']= array();
  if (!isset($_SESSION[$this->plug['name']]['ncart'])) $_SESSION[$this->plug['name']]['ncart']= 0;
  $nombre = $_POST["nb"];
  $_SESSION[$this->plug['name']]['ncart'] += $nombre;
  $_SESSION[$this->plug['name']]['prods'][$_POST['idP']] = $nombre;
  $_SESSION[$this->plug['name']]["msgProdUpDate"] = TRUE;
  header("Location: {$_SERVER["REQUEST_URI"]}");
  exit();
 }

 public function traitementPanier(){
  if (!isset($_SESSION)) session_start();
  if (!isset($_SESSION[$this->plug['name']]['prods'])) $_SESSION[$this->plug['name']]['prods'] = array();
  if (!isset($_SESSION[$this->plug['name']]['ncart'])) $_SESSION[$this->plug['name']]['ncart'] = 0;
  if (isset($_POST["retirerProduit"])){
   $cles = array_keys($_POST["retirerProduit"]);
   $idP = array_pop($cles);
   if (isset($_SESSION[$this->plug['name']]['prods'][$idP])){
    $_SESSION[$this->plug['name']]['ncart'] -= $_SESSION[$this->plug['name']]['prods'][$idP];
    unset($_SESSION[$this->plug['name']]['prods'][$idP]);
    $_SESSION[$this->plug['name']]["msgProdUpDate"] = TRUE;
   }
   header("Location: {$_SERVER["REQUEST_URI"]}");
   exit();
  }
  if (isset($_POST["recalculer"])){
   foreach ($_POST["nb"] as $idP => $nb){
    $nb = floor($nb);
    $nb = max(0, $nb);
    if (isset($_SESSION[$this->plug['name']]['prods'][$idP])){
     $_SESSION[$this->plug['name']]['ncart'] -= $_SESSION[$this->plug['name']]['prods'][$idP];
     if (0 === $nb){
      unset($_SESSION[$this->plug['name']]['prods'][$idP]);
     } else {
      $_SESSION[$this->plug['name']]['ncart'] += $nb;
      $_SESSION[$this->plug['name']]['prods'][$idP] = $nb;
     }
     $_SESSION[$this->plug['name']]["msgProdUpDate"] = TRUE;
    }
   }
   header("Location: {$_SERVER["REQUEST_URI"]}");
   exit();
  }
 }
}
