## PlxMyShop Change Log

##v0.10  18/03/2017##
* [+] Compatible PluXml 5.5, [5.6](https://github.com/pluxml/PluXml/releases/tag/5.6rc4)
* [+] Édition des produits compatible avec le plugin WymEditor et d'autres éditeurs (Changement de l'id form_produit VERS form_article)
* [+] Ajout du theme d'exemple static-boutique-produits-par-categories.php de Philippe Le Toquin : https://github.com/ppmt/plxMyShop/commit/411cc5e749fc53b9b2a54c064dd969d9f0c6db48
* [+] Intégration de dataTable.js pour l'affichage des commandes afin d'en simplifier le triage et les recherches
* [+] Ajout des nouvelles classes de pluCss et adaptation des boutons dans l'action-bar
* [+] Déplacement dans administration.css des styles html inside (dans le body) [template edit prod admin & ajax/select_img]
* [+] Ajout d'administration.css en javascript [config.php, template edit prod admin]
* [+] Ajout du nom du module en cour dans le titre de l'admin de la boutique
* [+] Complétion des fichiers de langues fr,en (manque quelques unes en occitan) 
* [+] Ré-indentation & Simplification du code (One Space Indent, boucles aux Frais De Ports, style, ...)
* FIX Champs du nombre de produits a commander (Possibilité d'en commander 0 ou -1 -2 -20 ...)
###### dans plxMyShop.php     
 - [ ] # //require PLX_PLUGINS . 'plxMyShop/classes/paypal_api/SetExpressCheckout.php'; c'est/était quoi?
 - [ ] require PLX_PLUGINS . 'plxMyShop/classes/paypal_api/boutonPaypalSimple.php'; (à tester)

##v0.9.9.0.dev  05/08/2016##
From develop branch of mathieu269 : [commit](https://github.com/davidlhoumaud/plxMyShop/commit/3f9df5b8656d989bec9827a9c0f2c477cf10758b)

##notes, todo & suggests 4 the future##
* tester paypal
* Utiliser la gallerie de Media native de PluXml?
* Faire évoluer les formulaires d'édition de produit (compatible PluCss) et de commande (panier coté public)
* Que le fait de cliquer sur "Ajouter au panier" additionne a ceux déjà présent dans le panier au lieu de remplacer le compte par le nouveau nombre (ou changer l'intitulé du champ).
* intégrer en interne? et/ou harmoniser jquery(version) front(cloudflare.com) & admin + jquery.dataTables & cdn
* "voir" une commande en mode smoothframe (avec jquery?)
* Vérifier comment il fonctionne sur pluxml <=5.4? (classes css pour la sidebar?)
* Verifier la récriture d'url (activé et ou avec MyBetterUrls)
* intégrer datatable.js pour la liste des produits et/ou des catégories de produits? (+comlexe)
* "voir" une commande en mode smoothframe (avec jquery?)

# Les Crochets (Hooks) du plugins 
      in plxMotorPreChauffageBegin() 
        eval($this->plxMotor->plxPlugins->callHook("plxMyShop_debut"));
      commentés pour le moment :
        in editProduct($content)
        # Hook Plugins
         //eval($this->plxPlugins->callHook('plxAdminEditProduct'));
        in plxShowProductInclude($id)
        # Hook Plugins
         //if(eval($this->plxMotor->plxPlugins->callHook('plxShowProductInclude'))) return ;