<?php include(dirname(__FILE__) . '/header.php'); ?>
 <main class="main grid" role="main">
  <section class="col sml-12">
   <article class="article static" role="article" id="static-page-<?php echo $plxShow->staticId(); ?>">
    <header>
     <h1>
      <?php $plxShow->staticTitle(); ?>
     </h1>
    </header>
    <section>
     <?php $plxShow->staticContent(); ?>
    </section>
   </article>
  </section>
  <section class="col sml-12">
<?php
    $plxMyShop = $plxShow->plxMotor->plxPlugins->aPlugins['plxMyShop'];
    $plxMyShop->donneesModeles["plxPlugin"] = $plxMyShop;

    if (isset($plxMyShop->aProds) && is_array($plxMyShop->aProds)) {
?>
      <script type="text/javascript" src="<?php echo $plxMyShop->plxMotor->racine . PLX_PLUGINS;?>plxMyShop/js/jquery.min.js"></script>
      <script type="text/javascript">
      jQuery.noConflict();
      </script>

      <script type='text/javascript' src='<?php echo $plxMyShop->plxMotor->racine . PLX_PLUGINS;?>plxMyShop/js/panier.js'></script>

      <script type='text/javascript'>
      var error = false;
      var devise = '<?php echo $plxMyShop->getParam("devise");?>';
      var pos_devise = '<?php echo $plxMyShop->getParam("pos_devise");?>';
      var shoppingCart = null;
      var L_FOR = '<?php echo $plxMyShop->getlang('L_FOR'); ?>';
      var L_DEL = '<?php echo $plxMyShop->getlang('L_DEL'); ?>';
      var L_TOTAL = '<?php echo $plxMyShop->getlang('L_TOTAL_BASKET'); ?>';
      </script>
<?php
     foreach ($plxMyShop->aProds as $kRubrique => $vRubrique) {
      if ( $vRubrique['menu'] === 'non'
       || $vRubrique['menu'] === ''
       || (1 !== $vRubrique["active"])
      ) {
       continue;
      }
      $plxMyShop->idProduit = $kRubrique;
      $lien = $plxShow->plxMotor->urlRewrite("?".$plxMyShop->lang."product$kRubrique/{$vRubrique["url"]}");
?>
       <h2><a href="<?php echo htmlspecialchars($lien);?>"><?php echo htmlspecialchars($vRubrique['name']);?></a></h2>
       <section class="list_products">
        <header>
         <div class="cat_image">
          <?php echo ($vRubrique["image"]!="") ? '<img class="product_image_cat" src="'.$plxMyShop->productImage().'">' : '';?>
         </div>
        </header>
<?php
         foreach($plxMyShop->aProds as $k => $v) {
          if (  strstr($v['group'],$kRubrique) 
           && $v['active']==1 
           && $v['readable']==1
          ) {
           $plxMyShop->donneesModeles["k"] = $k;
           $plxMyShop->modele("espacePublic/boucle/produitRubrique");
          }
         }
        ?>
       </section>
<?php
     }
    }
?>
  </section>
 </main>
<?php include(dirname(__FILE__).'/footer.php'); ?>