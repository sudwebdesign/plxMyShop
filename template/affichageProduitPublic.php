<?php
$plxShow = plxShow::getInstance();
$plxPlugin = $plxShow->plxMotor->plxPlugins->aPlugins['plxMyShop'];
$plxPlugin->donneesModeles["plxPlugin"] = $plxPlugin;
if (($plxPlugin->aProds[ $plxPlugin->productNumber()]['active']!=1 || $plxPlugin->aProds[ $plxPlugin->productNumber()]['readable']!=1) && ($plxPlugin->aProds[ $plxPlugin->productNumber()]['pcat']!=1)) header('Location: index.php');
?>
<script type="text/javascript" src="<?php echo $this->plxMotor->racine . PLX_PLUGINS;?>plxMyShop/js/jquery.min.js?afPP"></script>
<script type="text/javascript">
jQuery.noConflict();
</script>
<script type='text/javascript' src='<?php echo $plxPlugin->plxMotor->racine . PLX_PLUGINS;?>plxMyShop/js/panier.js'></script>
<script type='text/javascript'>
var error = false;
var devise = '<?php echo $plxPlugin->getParam("devise");?>';
var pos_devise = '<?php echo $plxPlugin->getParam("pos_devise");?>';
var L_FOR = '<?php echo $plxPlugin->getlang('L_FOR'); ?>';
var L_DEL = '<?php echo $plxPlugin->getlang('L_DEL'); ?>';
var L_TOTAL = '<?php echo $plxPlugin->getlang('L_TOTAL_BASKET'); ?>';
</script>
<?php
// e-mail de la commande
$_SESSION["plxMyShop"]['msgCommand']="";
$plxPlugin->validerCommande();
if ("1" === $plxPlugin->aProds[$plxPlugin->productNumber()]['pcat']) {
 $plxPlugin->modele("espacePublic/categorie");
} else {
 $plxPlugin->modele("espacePublic/produit");
}
if (in_array($plxPlugin->getParam("affPanier"), array("basPage", "partout"))){
 $plxPlugin->modele("espacePublic/panier");
}
//~ else {
 //~ $plxPlugin->modele("espacePublic/ajoutProduit");
//~ }