=== Simple Pictures Slider ===
Contributors: tombgtn
Tags: simple, slider, pictures, images, developers, hooks, compatible, acf, srcset, simple plugins, sps
Requires at least: 6.3.0
Tested up to: 6.6.2
Stable tag: 1.5.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple plugin to display a picture slider. Light, efficient and compatible with ACF. Best for developers.

== Description ==

SPS is a simple plugin to create a slider of pictures and display it with a shortcode or PHP code. Few options to keep it simple, but fast and efficient. Some hooks to improve the slider or to configure it more specifically. Good plugin for developers, with possibility to control what your client can edit or not in the advanced options.
Compatible with ACF, you can put field groups for every slide. It's possible to override template files for displaying the fields.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/simple-pictures-slider` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Sliders screen to create or edit sliders
4. Copy shortcode or PHP code and put it in your theme

== Screenshots ==

1. Possibilité de créer plusieurs sliders
2. Interface de modification du slider (modification des slides, réarrangement...)
3. Modification de la slide et options avancés
4. Intégration de champs ACF pour chaque slide
5. Multiple options de personnalisation
6. Autres options de personnalisation

== Changelog ==

= 1.0 =
* Création du plugin

= 1.1 =
* Possibilité d'afficher l'image en background

= 1.2 =
* Correction pour ES5

= 1.3 =
* Correction de la mise en pause au survol
* Ajout de classes au changement des slides

= 1.4 =
* Suppression des srcset natifs de wordpress
* Ajout de la fonctionnalité srcset
* Bugfix : affichage de la transition choisie dans le selecteur de l'admin

= 1.5 =
* Remplacement de la fonction wp_get_loading_attr_default déprécié par wp_get_loading_optimization_attributes
* Possibilité de filtrer les slides pour transformer le slider en ce que vous voulez