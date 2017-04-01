<?php
/*
Si vous réutilisez ce fichier dans votre thème, nous vous conseillons de noter la version actuelle de plxMyShop
version : 
*/
$plxPlugin = $d["plxPlugin"];
$plxPlugin->traitementPanier();
$afficheMessage = FALSE;
if ( isset($_SESSION["plxMyShop"]['msgCommand']) 
 && !empty($_SESSION["plxMyShop"]['msgCommand']) 
 && $_SESSION["plxMyShop"]['msgCommand']!=""
){
 $afficheMessage = TRUE;
 $message = $_SESSION["plxMyShop"]['msgCommand'];
 unset($_SESSION["plxMyShop"]['msgCommand']);
}
$cssCart=$this->plxMotor->racine.PLX_PLUGINS.'plxMyShop/css/panier.css';
?>
<script type="text/javascript">
 var s = document.createElement("link"); s.href = "<?php echo $cssCart;?>"; s.async = true; s.rel = "stylesheet"; s.type = "text/css"; s.media = "screen";
 var mx = document.getElementsByTagName('link'); mx = mx[mx.length-1]; mx.parentNode.insertBefore(s, mx.nextSibling);
</script>
<noscript><link rel="stylesheet" type="text/css" href="<?php echo $cssCart;?>" /></noscript>
<a id="panier"></a>
<div align="center" class="panierbloc">
 <div align="center" id="listproducts">
  <section align="center" class="productsect">
   <header><?php 
    if ($afficheMessage) {
     echo $message;
    } else {
    $plxPlugin->lang('L_PUBLIC_BASKET');
    }
   ?></header>
   <div id="shoppingCart" >
<?php
     $sessioncart="";
     $totalpricettc=0;
     $totalpoidg=0;
     $totalpoidgshipping = 0;
     $nprod=0;
     if (isset($_SESSION["plxMyShop"]['prods']) && $_SESSION["plxMyShop"]['prods']) {
?>
       <form action="" method="POST">
        <table class="tableauProduitsPanier">
         <tr>
          <th><?php $plxPlugin->lang('L_PRODUCT'); ?></th>
          <th><?php $plxPlugin->lang('L_UNIT_PRICE'); ?></th>
          <th><?php $plxPlugin->lang('L_NUMBER'); ?></th>
          <th><?php $plxPlugin->lang('L_TOTAL_PRICE'); ?></th>
          <th></th>
         </tr>
<?php
          foreach ($_SESSION["plxMyShop"]['prods'] as $pId => $nb) {
           if (!isset($plxPlugin->aProds[$pId])){continue;}
           $prixUnitaire = (float) $plxPlugin->aProds[$pId]['pricettc'];
           $prixttc = $prixUnitaire * $nb;
           $poidg = (float) $plxPlugin->aProds[$pId]['poidg'] * $nb;
           $totalpricettc += $prixttc;
           $totalpoidg += $poidg;
           $nprod++;
?>
          <tr>
           <td><?php echo $plxPlugin->aProds[$pId]['name'];?></td>
           <td class="nombre"><?php echo $plxPlugin->pos_devise($prixUnitaire);?></td>
           <td><input type="number" name="nb[<?php echo $pId;?>]" value="<?php echo htmlspecialchars($nb);?>" /></td>
           <td class="nombre"><?php echo $plxPlugin->pos_devise($prixttc);?></td>
           <td><input type="submit" name="retirerProduit[<?php echo $pId;?>]" value="<?php echo htmlspecialchars($plxPlugin->getLang('L_DEL'));?>" /></td>
          </tr>
         <?php } // FIN foreach ($_SESSION["plxMyShop"]['prods'] as $pId => $nb) {?>
        </table>
        <input type="submit" name="recalculer" value="<?php echo htmlspecialchars($plxPlugin->getLang('L_PANIER_RECALCULER'));?>" />
       </form>
       <?php $totalpoidgshipping = $plxPlugin->shippingMethod($totalpoidg, 1);?>
       <span id="spanshipping"></span>
       <span id='totalCart'><?php
        echo htmlspecialchars($plxPlugin->getLang('L_TOTAL_BASKET').
        (($plxPlugin->getParam("shipping_colissimo"))?$plxPlugin->getLang('L_TOTAL_BASKET_PORT'):'')).
        '&nbsp;:&nbsp;'.
        $plxPlugin->pos_devise($totalpricettc + $totalpoidgshipping);
       ?></span>
<?php
     }
    if (0 === $nprod && !$afficheMessage) {?>
     <em><?php $plxPlugin->lang('L_PUBLIC_NOPRODUCT'); ?></em>
<?php } ?>
   </div>
   <form id="formcart" method="POST" action="#panier">
    <p><span class='startw'><?php $plxPlugin->lang('L_PUBLIC_MANDATORY_FIELD'); ?></span></p>
    <p><strong id="labelFirstnameCart"><?php $plxPlugin->lang('L_PUBLIC_FIRSTNAME'); ?><span class='star'>*</span>&nbsp;:</strong> <input  type="text" name="firstname" id="firstname" value="">
    <strong id="labelLastnameCart"><?php $plxPlugin->lang('L_PUBLIC_LASTNAME'); ?><span class='star'>*</span>&nbsp;:</strong> <input type="text" name="lastname"  id="lastname" value=""></p>
    <p><strong id="labelMailCart"><?php $plxPlugin->lang('L_PUBLIC_EMAIL'); ?><span class='star'>*</span>&nbsp;:</strong> <input type="email" name="email"  id="email" value=""></p>
    <p><strong id="labelTelCart"><?php $plxPlugin->lang('L_PUBLIC_TEL'); ?>&nbsp;:</strong> <input type="text" name="tel" id="tel" value=""></p>
    <p><strong id="labelAddrCart"><?php $plxPlugin->lang('L_PUBLIC_ADDRESS'); ?><span class='star'>*</span>&nbsp;:</strong> <input type="text" name="adress" id="adress" value=""></p>
    <p><strong id="labelPostcodeCart" ><?php $plxPlugin->lang('L_PUBLIC_ZIP'); ?><span class='star'>*</span>&nbsp;:</strong> <input  type="text" name="postcode" id="postcode" value="">
    <strong id="labelCityCart"><?php $plxPlugin->lang('L_PUBLIC_TOWN'); ?><span class='star'>*</span>&nbsp;:</strong> <input type="text" name="city" id="city" value=""></p>
    <p><strong id="labelCountryCart"><?php $plxPlugin->lang('L_PUBLIC_COUNTRY'); ?><span class='star'>*</span>&nbsp;:</strong> <input type="text" name="country" id="country" value=""></p>
    <p><span id="bouton_sauvegarder">&nbsp;</span>&nbsp;<span id="bouton_effacer">&nbsp;</span>&nbsp;<span id="bouton_raz">&nbsp;</span></p>
    <p id="alerte_sauvegarder" class="alert green" style="display:none;">&nbsp;</p>
    <p>
     <label for="choixCadeau">
      <input type="checkbox" id="choixCadeau" name="choixCadeau"<?php echo (!isset($_POST["choixCadeau"])) ? "" : " checked=\"checked\"";?> />
      <?php $plxPlugin->lang('L_PUBLIC_GIFT'); ?>
     </label>
    </p>
    <p class="conteneurNomCadeau">
     <label for="nomCadeau">
      <?php $plxPlugin->lang('L_PUBLIC_GIFTNAME'); ?>
      <input type="text" name="nomCadeau" id="nomCadeau" value="<?php echo (!isset($_POST["nomCadeau"])) ? "" : htmlspecialchars($_POST["nomCadeau"]);?>" />
     </label>
    </p>
    <strong id="labelMsgCart"><?php $plxPlugin->lang('L_PUBLIC_COMMENT'); ?></strong><br><textarea name="msg" id="msgCart"  rows="3"></textarea><br>
    <textarea name="prods" id="prodsCart" rows="3"></textarea>
    <input type="hidden" name="total" id="totalcommand" value="0">
    <input type="hidden" name="shipping" id="shipping" value="0">
    <input type="hidden" name="shipping_kg" id="shipping_kg" value="0">
    <input type="hidden" name="idsuite" id="idsuite" value="0">
    <input type="hidden" name="numcart" id="numcart" value="0">
    <strong><?php $plxPlugin->lang('L_EMAIL_CUST_PAYMENT'); ?>&nbsp;:&nbsp;&nbsp;</strong><select onchange="changePaymentMethod(this.value);" name="methodpayment">
<?php
      $methodpayment = !isset($_SESSION["plxMyShop"]["methodpayment"]) ? "" : $_SESSION["plxMyShop"]["methodpayment"];
      foreach ($d["tabChoixMethodespaiement"] as $codeM => $m) {?>
      <option value="<?php echo htmlspecialchars($codeM);?>"<?php 
       echo ($codeM !== $methodpayment) ? "" : " selected=\"selected\"";
      ?>><?php echo htmlspecialchars($m["libelle"]);?></option>
<?php } ?>
    </select>
    <br />
<?php if ("" !== $plxPlugin->getParam("urlCGV")) {?>
     <label for="valideCGV">
      <input type="checkbox" name="valideCGV" id="valideCGV"<?php echo (!isset($_POST["valideCGV"])) ? "" : " checked=\"checked\"";?> />
      <span class='star'>*</span>
      <a href="<?php echo htmlspecialchars($plxPlugin->getParam("urlCGV"));?>"><?php echo htmlspecialchars($plxPlugin->getParam("libelleCGV"));?></a>
     </label>
<?php } ?>
    <input type="submit" id="btnCart" name="validerCommande" value="<?php $plxPlugin->lang('L_PUBLIC_VALIDATE_ORDER'); ?>"/><br>
   </form>
  </section>
 </div>
</div>
<div id="msgAddCart">&darr; <?php $plxPlugin->lang('L_PUBLIC_ADDBASKET'); ?> &darr;</div>
<script type="text/JavaScript">
<?php
 if ($nprod > 0 ) echo "var error=true;\n";
 else echo "var error=false;\n";
?>
var total=0;
var totalkg=0;
var shippingPrice=0;
var tmpship=0;
var nprod=0;
var realnprod=0;
var formCart=document.getElementById('formcart');
var shoppingCart=document.getElementById('shoppingCart');
var btnCart=document.getElementById('btnCart');
var msgCart=document.getElementById('msgCart');
var labelMsgCart=document.getElementById('labelMsgCart');
var PRODS=document.getElementById('prodsCart');
var msgAddCart=document.getElementById('msgAddCart');

var idSuite=document.getElementById('idsuite');
var numCart=document.getElementById('numcart');

var mailCart=document.getElementById('email');
var labelMailCart=document.getElementById('labelMailCart');
var firstnameCart=document.getElementById('firstname');
var labelFirstnameCart=document.getElementById('labelFirstnameCart');
var lastnameCart=document.getElementById('lastname');
var labelLastnameCart=document.getElementById('labelLastnameCart');

var adressCart=document.getElementById('adress');
var labelAddrCart=document.getElementById('labelAddrCart');
var postcodeCart=document.getElementById('postcode');
var labelPostcodeCart=document.getElementById('labelPostcodeCart');
var cityCart=document.getElementById('city');
var labelCityCart=document.getElementById('labelCityCart');
var countryCart=document.getElementById('country');
var labelCountryCart=document.getElementById('labelCountryCart');

var telCart=document.getElementById('tel');
var labelTelCart=document.getElementById('labelTelCart');

var totalCart=document.getElementById('totalCart');
var totalcommand=document.getElementById('totalcommand');
var shipping=document.getElementById('shipping');
var shipping_kg=document.getElementById('shipping_kg');
var spanshipping=document.getElementById('spanshipping');

if (error) {
 PRODS.value=shoppingCart.innerHTML;
 
 formcart.style.display='inline-block';
 
 btnCart.style.display='inline-block';
 msgCart.style.display='inline-block';
 labelMsgCart.style.display='inline-block';
 
 mailCart.style.display='inline-block';
 mailCart.value="<?php echo (isset($_POST['email'])?$_POST['email']:''); ?>";
 labelMailCart.style.display='inline-block';
 
 firstnameCart.style.display='inline-block';
 firstnameCart.value="<?php echo (isset($_POST['firstname'])?preg_replace('/\"/','\\\"',$_POST['firstname']):''); ?>";
 labelFirstnameCart.style.display='inline-block';
 
 lastnameCart.style.display='inline-block';
 lastnameCart.value="<?php echo (isset($_POST['lastname'])?preg_replace('/\"/','\\\"',$_POST['lastname']):''); ?>";
 labelLastnameCart.style.display='inline-block';
 
 adressCart.style.display='inline-block';
 adressCart.value="<?php echo (isset($_POST['adress'])?preg_replace('/\"/','\\\"',$_POST['adress']):''); ?>";
 labelAddrCart.style.display='inline-block';
 
 postcodeCart.style.display='inline-block';
 postcodeCart.value="<?php echo (isset($_POST['postcode'])?preg_replace('/\"/','\\\"',$_POST['postcode']):''); ?>";
 labelPostcodeCart.style.display='inline-block';
 
 cityCart.style.display='inline-block';
 cityCart.value="<?php echo (isset($_POST['city'])?preg_replace('/\"/','\\\"',$_POST['city']):''); ?>";
 labelCityCart.style.display='inline-block';
 
 countryCart.style.display='inline-block';
 countryCart.value="<?php echo (isset($_POST['country'])?preg_replace('/\"/','\\\"',$_POST['country']):''); ?>";
 labelCountryCart.style.display='inline-block';
 
 telCart.style.display='inline-block';
 telCart.value="<?php echo (isset($_POST['tel'])?preg_replace('/\"/','\\\"',$_POST['tel']):''); ?>";
 labelTelCart.style.display='inline-block';
 
 idSuite.value="<?php echo (isset($_SESSION["plxMyShop"]['ncart'])?$_SESSION["plxMyShop"]['ncart']:''); ?>";
 numCart.value="<?php echo (isset($_SESSION["plxMyShop"]['ncart'])?$_SESSION["plxMyShop"]['ncart']:''); ?>";
 nprod=<?php echo (isset($_SESSION["plxMyShop"]['ncart'])?(int)$_SESSION["plxMyShop"]['ncart']:0); ?>;
 realnprod=<?php echo (isset($_SESSION["plxMyShop"]['ncart'])?(int)$_SESSION["plxMyShop"]['ncart']:0); ?>;
 tmpship=<?php echo (isset($totalpoidgshipping)?$totalpoidgshipping:0.00); ?>;
 total=<?php echo (isset($totalpricettc)?$totalpricettc:0.00); ?>;
 if (total >0) displayTotal=(total+<?php echo (isset($totalpoidgshipping)?$totalpoidgshipping:0.00); ?>);
 else displayTotal=0;

 pos_devise= "<?php echo $plxPlugin->getParam("position_devise");?>";
 devise= "<?php echo $plxPlugin->getParam("devise");?>";
 
 if (pos_devise == "before") { price= devise+displayTotal.toFixed(2);}
 else { price= displayTotal.toFixed(2)+devise;}
 
 //totalCart.innerHTML="<?php $plxPlugin->lang('L_TOTAL_BASKET'); ?>&nbsp;: "+price; 
<?php if ($plxPlugin->getParam("shipping_colissimo")):?>
 if (pos_devise == "before") { price= devise+"<?php echo (isset($totalpoidgshipping)?$totalpoidgshipping:0.00); ?>";}
 else { price= "<?php echo (isset($totalpoidgshipping)?$totalpoidgshipping:0.00); ?>&nbsp;"+devise;}
 spanshipping.innerHTML="<p class='spanshippingp'><?php $plxPlugin->lang('L_EMAIL_DELIVERY_COST'); ?>&nbsp;: " + price + " <?php $plxPlugin->lang('L_FOR'); ?> <?php echo $totalpoidg; ?>&nbsp;kg</p>";
<?php endif; ?>
 totalcommand.value=total;
}

function changePaymentMethod(method) {
 if (method=="cheque")formCart.action="#panier";
 else if (method=="cash") formCart.action="#panier";
 else if (method=="paypal") formCart.action="#panier";
}

function shippingMethod(kg, op){
 if (op==1)totalkg=(parseFloat(totalkg.toFixed(3))+parseFloat(kg));
 if (op==0)totalkg=(parseFloat(totalkg.toFixed(3))-parseFloat(kg));
 accurecept=<?php echo (float)$plxPlugin->getParam('acurecept'); ?>;
 if (totalkg.toFixed(3)<=0.000) {
  shippingPrice=accurecept;
 }<?php #beau js
for($i=1;$i<=11;$i++){
  $num=str_pad($i, 2, "0", STR_PAD_LEFT); 
  ?>else if (totalkg.toFixed(3)<=<?php echo (float)$plxPlugin->getParam('p'.$num); ?>) {
  shippingPrice=<?php echo (float)$plxPlugin->getParam('pv'.$num); ?>+accurecept;
 }<?php 
}#en php ?>

 return shippingPrice;
}
</script>
<script>
 // localStorage du formulaire pour les clients
 if (window.localStorage) {
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
    document.getElementById("alerte_sauvegarder").innerHTML = "<?php $plxPlugin->lang('L_ADDRESS_SAVED'); ?><br><?php $plxPlugin->lang('L_DO_NOT_SHARED'); ?>";
    document.getElementById("alerte_sauvegarder").style.display = "block";
    setTimeout(function(){
    document.getElementById("alerte_sauvegarder").style.display = "none"; }, 3000);
   }
   function clear(){
    localStorage.removeItem("Shop_Deliver_Adress"); 
    document.getElementById("alerte_sauvegarder").innerHTML = "<?php $plxPlugin->lang('L_ADDRESS_DELETED'); ?>";
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
   var gm =  JSON.parse(localStorage.getItem("Shop_Deliver_Adress"));
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
   input_un.setAttribute("value","<?php $plxPlugin->lang('L_SAVE_MY_ADDRESS'); ?>");
   input_un.setAttribute("type","button");
   input_un.addEventListener("click",stock, false);
   bouton_un.appendChild(input_un);

   var bouton_deux = document.getElementById("bouton_effacer");
   input_deux = document.createElement("input");
   input_deux.setAttribute("name","ClearAdress");
   input_deux.setAttribute("value","<?php $plxPlugin->lang('L_DELETE_MY_ADDRESS'); ?>");
   input_deux.setAttribute("type","button");
   input_deux.addEventListener("click",clear, false);
   bouton_deux.appendChild(input_deux);

   var bouton_raz = document.getElementById("bouton_raz");
   input_raz = document.createElement("input");
   input_raz.setAttribute("name","RAZAdresse");
   input_raz.setAttribute("value","<?php $plxPlugin->lang('L_RESET_ADDRESS'); ?>");
   input_raz.setAttribute("type","button");
   input_raz.addEventListener("click",raz, false);
   bouton_raz.appendChild(input_raz);
  }
 }
</script>