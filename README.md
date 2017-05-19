irivenPhpCache
==============
gestionnaire de cache multi plugins en php avec chargement automatique du driver par defaut. 

les drivers pris en charge sont:
- Apc
- eaccelerator
- sqlite
- files
- memcache
- memcached
- wincache
- Xcache

- si aucun driver n'est passé en parametre lors de l'initialisation de la classe, alors le mode 'files' est automatiquement choisi.
parcontre si apres ce choix, il detecte que l'extention php pdo_sqlite est active, le mode 'files' est abandonné au profit de 'sqlite'.
l'une des particularités de cette classe reside dans le fait que si vous renseignez un driver innexistant ou dont l'extension php n'est pas active, elle vas egalement choisir de maniere automatique et selon l'ordre de priorité definit dans le code, un driver parmi ceux disponibles et actifs evitant ainsi des rapports d'erreurs intempestifs.

- si le repertoire de stockage n'est pas defini, lors de l'utilisation en mode 'files' ou 'sqlite'
le repertoire repertoire de stockage des fichiers temporaires du serveur est choisi(sous windows: c:/Windows/Temp/; sous linux: /tmp/). mais sachez que dans cette situation, en cas de redemarrage du serveur les fichiers en cache seront detruits. et le chargement des pages sera à nouveau lent, mais au fure et à mesure que le site sera visité, il deviendra rapide puisque le cache sera reconstitué.

nous vous encourageons donc à contribuer activement au developpement de ce projet, 
soit à travers vos retours d'experiences utilisateurs, soit en nous signalant déventuels bugs.



EXAMPLE
========
   $cache= new irivenPhpCache('files',array('path'=>CACHEPATH,'lifetime'=>'3600'));
   
   if(!$cache->get('dejeuner')) $cache->set('dejeuner','un demi pain,une orange et une tasse de lait');
   
   if(!$test = $cache->get('famille')) $cache->set('famille','les parents et les enfants');
   
   echo $cache->get('famille'). ' - '.$cache->get('dejeuner');
   
   $txt = sprintf("%s ont pris chacun au petit-dejeuné: %s.",$cache->get('famille'),$cache->get('dejeuner'));
   
   echo $txt;
   
   print_r($cache->stats());
   
   $cache->clear();

## Donation

If this project help you reduce time to develop, you can give me a cup of coffee :)

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XDCFPNTKUC4TU)

