=== Ceceppa Multilingua ===
Contributors: ceceppa
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, professional, translation, service, human, qtranslate, wpml, ztranslate, xtranslate
Requires at least: 3.4.1
Tested up to: 3.5.1
Stable tag: 0.3.7
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate Link: http://www.ceceppa.eu/ceceppa-multilingua/

Adds userfriendly multilingual content management and translation support into Wordpress.

== Description ==

I create Ceceppa Multilingua to let Wordpress have an easy to use interface for managing a fully multilingual web site.
With "Ceceppa Multilingua" you can write your posts and pages in multiple language. Here are some features:

- Separated posts and pages for each languages, so you can use different SEO and url for each languages.
- Different menu for each language.
- Translate widget's title.
- Group/Ungroup comments for each post's languages.
- Show notice when the post/page/category that user is viewing is available, based on the information provided by the browser, in their its language
- Redirects the browser depending on the user's language. Append the suffix &lang= to the home url-
- Widget for language chooser
- Least Read Posts, Most Commented, Most Read Posts can show only the posts in user selected language
- Filter search in accordingly to current language
- Change wordpress locale according to current language, useful for localized themes
- Show the list flag of available languages on top or bottom of page/post

Ceceppa Multilingua supports infinite language, which can be easily added/modified/deleted via the comfortable Configuration Page.
All you need to do is activate the plugin, configure categories and start writing the content!

For more Information visit the [Plugin Homepage](http://www.ceceppa.eu/ceceppa-multilingua/)

Flags directory are downloaded from [Flags] (http://blog.worldofemotions.com/danilka/)
Some icons from [Icons] (http://www.iconfinder.com/)
Directions icon from (http://emey87.deviantart.com/)
Tooltip plugin for Jquery [Tipsy] (http://onehackoranother.com/projects/jquery/tipsy/)
jQuery image dropdown [DD] (http://www.marghoobsuleman.com/jquery-image-dropdown)

== Installation ==

For more detailed instructions, take a look at the [Installation Guide](http://www.ceceppa.eu/it/pillole/wp-guide/ceceppa-multilingua-configurare-e-utilizzare-il-plugin/)

Installation of this plugin is fairly easy:

1. Download the plugin from wordpress. 
1. Extract all the files. 
1. Upload everything (keeping the directory structure) to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Manage desidered languages

== Frequently Asked Questions ==

The FAQ is available at the [Plugin Homepage](http://www.ceceppa.eu/it/pillole/wp-guide/ceceppa-multilingua-configurare-e-utilizzare-il-plugin/)

For Problems visits the [Support page](http://www.ceceppa.eu/it/pillole/wp-guide/ceceppa-multilingua-configurare-e-utilizzare-il-plugin/)

== Screenshots ==

1. Language configuration
2. List of all posts with their translations
3. Translate widget's title
4. Plugin configuration
5. Link to the article
6. Menus configuration


== Changelog ==

= 0.3.7 =
* Fixed: Plugin doesn't work when table prefix wasn't "wp_"

= 0.3.6 =
* Fix error in options page.

= 0.3.5 =
* Get language info correctly during installation

= 0.3.4 =
* Fixed setlocale. Now locale will be changed correctly.
  Fixed linked categories. Now categories will be linked correctly, so filter post in homepage work correctly.
                           If you upgade from 0.3.3 or above, you must edit all linked categories by choosing
                           "Edit" from category page and save it.
= 0.3.3 =
* Fixed: setlocale. It was changed only in admin page

= 0.3.2 =
* Fixed same Notice in debug mode

= 0.3.1 =
* Added flags near title in "All posts" and "All pages
* Added checkbox for disable language

= 0.3 =
* Different post/page for each language
* Different menu for each language. (need to edit header.php)	 	
* Translate widget’s titles	 	
* Group/Ungroup comments for this post/page/category that are available in each language	 	
* Show notice when the post/page/category is available in the visitor’s language	 	
* Automatically redirects the browser depending on the user’s language	 	
* Widget for language chooser		
* Filter some wordpress widgets, as “Least and Reads Posts”, “Most read post”, “Most commented”		
* Filter search in accordingly to current language		
* Change wordpress locale according to current language, useful for localized themes		
* Show the list flag of available languages on top or bottom of page/post		
* Show list of all articles with their translatios, if exists