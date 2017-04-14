<?php
/**
 * Edition des produits
 * @package PLX
 * @author    David L
 **/

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN, PROFIL_MANAGER);

# On édite les produits
$fakeget=$onglet='';
if(!empty($_POST)){
 $plxPlugin->editProducts($_POST, true);
 if (isset($_POST['prod']) && !empty($_POST['prod'])){
  $plxPlugin->editProduct($_POST);
  $fakeget='&prod='.$_POST['prod'];
 } else {
  $plxPlugin->editProducts($_POST);
  $fakeget=(isset($_GET['mod']) && !empty($_GET['mod'])?'&mod='.$_GET['mod']:'');
 }
 header('Location: plugin.php?p=plxMyShop'.$fakeget);
 exit;
}

$dir = PLX_ROOT."data/commandes/";
if (isset($_GET['kill']) && !empty($_GET['kill']) && is_file($dir.$_GET['kill'])){
 unlink($dir.$_GET['kill']);
 header('Location: plugin.php?p=plxMyShop&mod=cmd');
}

if ((isset($_GET['prod']) && !empty($_GET['prod'])) || (isset($_POST['prod']) && !empty($_POST['prod'])))
   include(dirname(__FILE__).'/template/editionProduitAdmin.php');
else {
# On inclut le header
//include(dirname(__FILE__).'/top.php');
?>
<script type="text/javaScript">
function checkBox(obj){
 obj.value = (obj.checked==true) ? '1': '0';
}
</script>
<?php
 if (!isset($_GET["mod"])){
  $onglet = "produits";
  $titre = $plxPlugin->getLang("CREATE_PRODUCTS");
 }elseif("cat" === $_GET["mod"]){
  $onglet = "categories";
  $titre = $plxPlugin->getLang("CREATE_CATS");
 }elseif("cmd" === $_GET["mod"]){
  $onglet = "commandes";
  $titre = $plxPlugin->getLang("LIST_ORDERS");
 }

 $cssAdmn = PLX_PLUGINS.get_class($plxPlugin).'/css/administration.css';
?>
<script type="text/javascript">
 var s = document.createElement("link"); s.href = "<?php echo $cssAdmn;?>"; s.async = true; s.rel = "stylesheet"; s.type = "text/css"; s.media = "screen";;
 var mx = document.getElementsByTagName('link'); mx = mx[mx.length-1]; mx.parentNode.insertBefore(s, mx.nextSibling);
</script>
<noscript><link rel="stylesheet" type="text/css" href="<?php echo $cssAdmn;?>" /></noscript>

<h2 id="pmsTitle" class="in-action-bar page-title hide"><?php echo plxUtils::strCheck($titre);?></h2>
<script type="text/javascript">//surcharge du titre dans l'admin
 var title = document.getElementById('pmsTitle').innerHTML;
 document.getElementsByClassName('inline-form')[0].firstChild.nextSibling.innerHTML = 'plxMyShop - '+title;
</script>
<p class="in-action-bar plx<?php echo str_replace('.','-',@PLX_VERSION); echo defined('PLX_MYMULTILINGUE')?' multilingue':'';?>"><?php $plxPlugin->menuAdmin($onglet);?></p>

<form action="plugin.php?p=plxMyShop<?php echo (isset($_GET['mod']) && $_GET['mod']=='cat'?"&mod=cat":""); ?>" method="post" id="form_products">
 <?php if (!isset($_GET['mod']) || (isset($_GET['mod']) && $_GET['mod']!='cmd')): ?>
  <p>
   <?php echo plxToken::getTokenPostMethod() ?>
   <?php plxUtils::printSelect('selection', array( '' =>L_FOR_SELECTION, 'delete' =>L_DELETE), '', false, '', 'id_selection') ?>
   <input class="button submit" type="submit" name="submit" value="<?php echo L_OK ?>" onclick="return confirmAction(this.form, 'id_selection', 'delete', 'idProduct[]', '<?php echo L_CONFIRM_DELETE ?>')" />
   <input class="button update" type="submit" name="update" value="<?php $plxPlugin->lang('L_ADMIN_MODIFY') ?> <?php echo (isset($_GET['mod']) && $_GET['mod']=='cat'?$plxPlugin->getlang('L_CATEGORIES'):$plxPlugin->getlang('L_PRODUCTS')); ?>" />
  </p>
 <?php endif; ?>
  <div class="scrollable-table">
   <table id="myShop-table" class="table full-width listeCategoriesProduitsAdmin liste<?php echo (isset($_GET['mod']) && $_GET['mod']=='cat'?"Categories":"Produits");?>Admin">
    <thead>
     <tr>
<?php if (!isset($_GET['mod']) || (isset($_GET['mod']) && $_GET['mod']!='cmd')): ?>
      <th><input type="checkbox" onclick="checkAll(this.form, 'idProduct[]')" /></th>
      <th><?php $plxPlugin->lang('L_PRODUCTS_ID') ?></th>
      <th></th>
      <th><?php $plxPlugin->lang('L_PRODUCTS_TITLE') ?></th>
      <th><?php $plxPlugin->lang('L_PRODUCTS_URL') ?></th>
 <?php if (isset($_GET['mod']) && $_GET['mod']=='cat'){ ?>
      <th><?php $plxPlugin->lang('L_CATEGORIE_ACTIVE') ?></th>
 <?php } else { ?>
      <th><?php $plxPlugin->lang('L_PRODUIT_ACTIF') ?></th>
 <?php } ?>
      <th><?php $plxPlugin->lang('L_PRODUCTS_ORDER') ?></th>
 <?php if (isset($_GET['mod']) && $_GET['mod']=='cat'){ ?>
      <th><?php $plxPlugin->lang('L_PRODUCTS_MENU')?></th>
 <?php } else { ?>
      <th><?php $plxPlugin->lang('L_PRODUCTS_PRICE')?></th>
      <th><?php $plxPlugin->lang('L_PRODUCTS_WEIGHT')?></th>
 <?php } ?>
      <th><?php $plxPlugin->lang('L_PRODUCTS_ACTION') ?></th>
 <?php else: ?>
      <th><?php $plxPlugin->lang('L_DATE') ?></th>
      <th><?php $plxPlugin->lang('L_PAIEMENT') ?></th>
      <th><?php $plxPlugin->lang('L_MONTANT') ?></th>
      <th><?php $plxPlugin->lang('L_ACTIONS') ?></th>
<?php endif; ?>
     </tr>
    </thead>
   <tbody>
<?php
    # Initialisation de l'ordre
    $num = 0;
    # Si on a des produits
 if($plxPlugin->aProds){
  foreach($plxPlugin->aProds as $k=>$v){ # Pour chaque produit
   $url=$v['url'];
   if ((isset($_GET['mod']) && $_GET['mod']=='cat' && $v['pcat']!=1)||(isset($_GET['mod']) && $_GET['mod']=='cmd'))continue;
   if (!isset($_GET['mod']) && $v['pcat']==1)continue;

   $ordre = ++$num;
   $selected = $v['pcat']==1 ? ' checked="checked"' : '';
   $valued = $v['pcat']==1 ? '1' : '0';

   echo '
   <tr class="line-'.($num%2).'">
    <td><input type="checkbox" name="idProduct[]" value="'.$k.'" /><input type="hidden" name="productNum[]" value="'.$k.'" /></td>
    <td>'.$k.'
   <input type="hidden" name="'.$k.'_pcat" value="'.$valued.'"'.$selected.' onclick="checkBox(this);" />
   </td>'.PHP_EOL;
?>
   <td>
<?php
   $image = $v["image"];
   echo ($image!=""?'<img class="product_image" src="'.PLX_ROOT.$plxPlugin->cheminImages.$image.'">':'');
?>
   </td>
   <?php
   echo '<td>';
   plxUtils::printInput($k.'_name', plxUtils::strCheck($v['name']), 'text', '20-255');
   echo '</td><td>';
   plxUtils::printInput($k.'_url', $v['url'], 'text', '12-255');
   echo '</td><td>';
   plxUtils::printSelect($k.'_active', array('1'=>L_YES,'0'=>L_NO), $v['active']);
   echo '</td><td>';
   plxUtils::printInput($k.'_ordre', $ordre, 'text', '2-3');
   echo '</td>';

   if ($v['pcat']==1){
    echo '<td>';
    plxUtils::printSelect($k.'_menu', array('oui'=>L_DISPLAY,'non'=>L_HIDE), $v['menu']);
    echo '</td>';
   } else {
    echo '<td class="nombre">';
    if ($v["pricettc"] > 0){
     echo $plxPlugin->pos_devise($v["pricettc"]);
    }
    echo '</td>';
    echo '<td class="nombre">';
    if ($v["poidg"] > 0){
     echo $v["poidg"];
    }
    echo '</td>';
   }

   if(!plxUtils::checkSite($v['url'])){
    echo '<td>';
    echo '<a href="plugin.php?p=plxMyShop&prod='.$k.'" title="';
    $plxPlugin->lang('L_PRODUCTS_SRC_TITLE');
    echo '">';
    $plxPlugin->lang('L_PRODUCTS_SRC');
    echo '</a>';
    if($v['active']){
     echo '&nbsp;-&nbsp;<a href="'.$plxAdmin->urlRewrite('index.php?product'.intval($k).'/'.$url).'" title="Visualiser '.plxUtils::strCheck($v['name']).' sur le site">'.L_VIEW.'</a>';
    }
    echo '</td></tr>';
   }
   elseif($url[0]=='?')
    echo '</td><td>b <a href="'.$plxAdmin->urlRewrite('index.php?product'.intval($k).'/'.$url).'" title="'.plxUtils::strCheck($v['name']).'">'.L_VIEW.'</a></td></tr>';
   else
    echo '</td><td>c <a href="'.$plxAdmin->urlRewrite('index.php?product'.intval($k).'/'.$url).'" title="'.plxUtils::strCheck($v['name']).'">'.L_VIEW.'</a></td></tr>';
  }
  # On récupère le dernier identifiant
  $a = array_keys($plxPlugin->aProds);
  rsort($a);
 } else {
  $a['0'] = 0;
 }
 $new_productid = str_pad($a['0']+1, 3, "0", STR_PAD_LEFT);
 if (!isset($_GET['mod']) || (isset($_GET['mod']) && $_GET['mod']!='cmd')): ?>
  <tr class="new">
   <td>&nbsp;</td>
   <td><?php echo (isset($_GET['mod']) && $_GET['mod']=='cat'?$plxPlugin->getlang('L_NEW_CATEGORY'):$plxPlugin->getlang('L_NEW_PRODUCT')); ?></td>
   <?php
    echo '<input type="hidden" name="productNum[]" value="'.$new_productid.'" />'; ?>
   <td><input title="<?php $plxPlugin->lang('L_CAT') ?><?php echo '" type="hidden" name="'.$new_productid.'_pcat" value="'.(isset($_GET['mod']) && $_GET['mod']=='cat'?'1':'0').'" '.(isset($_GET['mod']) && $_GET['mod']=='cat'?'checked':'').' onclick="checkBox(this);" ></td>';
    echo '<td>';
    plxUtils::printInput($new_productid.'_name', '', 'text', '20-255');
    plxUtils::printInput($new_productid.'_template', $plxPlugin->getParam('template'), 'hidden');
    echo '</td><td>';
    plxUtils::printInput($new_productid.'_url', '', 'text', '12-255');
    echo '</td><td>';
    plxUtils::printSelect($new_productid.'_active', array('1'=>L_YES,'0'=>L_NO), '0');
    echo '</td><td>';
    plxUtils::printInput($new_productid.'_ordre', ++$num, 'text', '2-3');
    echo '</td>';
    if (isset($_GET['mod']) && $_GET['mod']=='cat'){
     echo '<td>';
     plxUtils::printSelect($new_productid.'_menu', array('oui'=>L_DISPLAY,'non'=>L_HIDE), '0');
     echo '</td>';
    } else {
     echo "<td colspan=\"3\">&nbsp;</td>";
    }
?>
  </tr>
<?php else:

 $dh  = opendir($dir);
 $filescommande= array();
 while (false !== ($filename = readdir($dh))){
  if (is_file($dir.$filename) && $filename!='.' && $filename!='..' && $filename!='index.html'){
   $filescommande[] = $filename;
  }
 }
 rsort($filescommande);
 while (list ($key, $val) = each ($filescommande) ){
  $namearray=preg_split('/_/',$val);
  $date=preg_split('/-/',$namearray[0]);
  echo '<tr>'.
   '   <td>'.$date[2].'-'.$date[1].'-'.$date[0].' &agrave; '.preg_replace('/-/',':',$namearray[1]).'</td>'.
   '   <td>'.$namearray[2].'</td>'.
   '   <td class="nombre">'.$plxPlugin->pos_devise((float)$namearray[3]+(float)preg_replace('/.html/','',$namearray[4])).'</td>'.
   '   <td><a onclick="if(confirm(\''.$plxPlugin->getlang('L_ADMIN_CONFIRM_DELETE').'\')) return true; else return false;" href="plugin.php?p=plxMyShop&mod=cmd&kill='.$val.'">'.$plxPlugin->getlang('L_ADMIN_ORDER_DELETE').'</a> - <a href="'.$dir.$val.'" target="_BLANK">'.$plxPlugin->getlang('L_ADMIN_ORDER_VIEW').'</a></td>'.
   '</tr>';
 }; 

 endif; ?>
    </tbody>
   </table>
  </div>
</form>

<?php } 
if($onglet=='commandes')
 include('datatables.js.php');