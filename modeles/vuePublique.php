
<script type='text/javascript' src='<?php echo $d["plxPlugin"]->plxMotor->racine . PLX_PLUGINS;?>plxMyShop/js/libajax.js'></script>
<script type='text/javascript' src='<?php echo $d["plxPlugin"]->plxMotor->racine . PLX_PLUGINS;?>plxMyShop/js/panier.js'></script>

<script type='text/javascript'>

var error = false;
var repertoireAjax = '<?php echo $d["plxPlugin"]->plxMotor->racine . PLX_PLUGINS;?>plxMyShop/ajax/';
var devise = '<?php echo $d["plxPlugin"]->getParam("devise");?>';
var shoppingCart = null;

</script>

<?php

// e-mail de la commande

$_SESSION['msgCommand']="";


if (isset($_POST['prods']) && ($_POST['prods'] !== "")) {
	$d["plxPlugin"]->validerCommande();
}

$this->vue->affichageVuePublique($d["plxPlugin"]);


