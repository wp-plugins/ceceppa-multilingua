<?php 
  global $wpCeceppaML;

  //Non posso richiamare lo script direttamente dal browser :)
  if(!is_object($wpCeceppaML)) die("Access denied");
  
  add_meta_box("cml_functions_page_languages", __('Language', 'ceceppaml') . ':', "cml_functions_box_language", "cml_functions_page");
  add_meta_box("cml_functions_page_flags", __('Flags', 'ceceppaml') . ':', "cml_functions_box_flags", "cml_functions_page");
  add_meta_box("cml_functions_page_menu", __('Utilies', 'ceceppaml') . ':', "cml_functions_box_menu", "cml_functions_page");

  function cml_functions_box_language() {
?>
    <ul class="cml_list">
      <li><a href="#current-id"><?php _e('Get the id of current language', 'ceceppaml') ?></a></li>
      <li><a href="#is-default"><?php _e('Check if current language is the default one', 'ceceppaml') ?></a></li>
      <li><a href="#lang-name"><?php _e('Get the name of the language', 'ceceppaml') ?></a></li>
      <li><a href="#all-langs"><?php _e('Get all languages', 'ceceppaml') ?></a></li>
      <hr style="color: #ddd; background-color: #ddd; height: 1px; border: none"/>
      <li><a href="#all-infos"><?php _e('Get all infos about language', 'ceceppaml') ?></a></li>
      <li><a href="#default-id"><?php _e('Get the id of default language', 'ceceppaml') ?></a></li>
      <li><a href="#byslug"><?php _e('Get language by slug', 'ceceppaml') ?></a></li>
    </ul>
<?php
  }


  function cml_functions_box_flags() {
?>
    <ul class="cml_list">
      <li><a href="#show-all"><?php _e('Show all available languages', 'ceceppaml') ?></a></li>
      <li><a href="#dropdown"><?php _e('HTML dropdown list of languages', 'ceceppaml') ?></a></li>
      <li><a href="#by-locale"><?php _e('Get the path of a flag by language locale', 'ceceppaml') ?></a></li>
      <li><a href="#by-id"><?php _e('Get the path of a flag by language id', 'ceceppaml') ?></a></li>
      <li><a href="#by-slug"><?php _e('Get the path of a flag by language slug', 'ceceppaml') ?></a></li>
    </ul>
<?php
  }


  function cml_functions_box_menu() {
?>
    <ul class="cml_list">
      <li><a href="#menu"><?php _e('Get the menu associated to current language', 'ceceppaml') ?></a></li>
      <li><a href="#menuname"><?php _e('Get the name of the menu associated to current language', 'ceceppaml') ?></a></li>
      <hr style="color: #ddd; background-color: #ddd; height: 1px; border: none"/>
      <li><a href="#getlang"><?php _e('Get the language of page/post', 'ceceppaml') ?></a></li>
      <li><a href="#setlang"><?php _e('Set the language of page/post', 'ceceppaml') ?></a></li>
    </ul>
<?php
  }

?>

<h3>Index</h3>
<div class="column-3">
  <?php do_meta_boxes('cml_functions_page','advanced',null); ?>
</div>

<div id="current-id"><br />
<h3><?php _e('Get the id of current language', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_get_current_language_id(); ?&gt;
  </pre>
</blockquote>
</div>

<div id="is-default"><br />
<h3><?php _e('Check if current language is the default one', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_is_default_lang($id = null); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
      <ul style="float: none; list-style: circle;padding-left: 50px;">
	<li><strong>$id</strong> <i>(string)</i>: - The id of language to be verified</li>
      </ul>
  </blockquote>
  <blockquote>
    <span class="example"><?php _e('Example:', 'ceceppaml') ?></span>
    <pre>
      &lt;?php;
	$menu = cml_get_menu();
	wp_nav_menu(array('theme_location' => $menu));
      ?&gt;
    </pre>
  </blockquote>
</blockquote>
</div>

<div id="lang-name"><br />
<h3><?php _e('Get the name of the language', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_get_language_title($id = null); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$id:</strong> <i>(integer)</i> - Id of the language. If null return the title of current language.</li>
	</ul>  
    </blockquote>
</blockquote>
</div>

<div id="all-langs"><br />
<h3><?php _e('Get all languages', 'ceceppaml') ?></h3>
<blockquote>
  Get configured languages from database.
  <pre class="code">
    &lt;?php cml_get_languages($enabled = 1, $default = 1); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
    <br />
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$enabled</strong> <i>(boolean)</i>: - If true return only enabled languages.</li>
	  <li><strong>$default</strong> <i>(boolean)</i>: - If true include also default language, otherwhise exclude it.</li>
	</ul>
      <br />
      <span class="return"><?php _e('Return:', 'ceceppaml') ?></span>
      <p style="padding-left: 20px">
	This function return an object and Its fields are:

	  <pre>
	  *) id           - id of language
	  *) cml_default  - 1 if it is the default language
	  *) cml_flag     - name of flag
	  *) cml_language - name of the language
	  *) cml_language_slug - slug of the language
	  *) cml_locale        - wordpress locale
	  *) cml_sort_id       - language order
	  </pre>
      </p>
    </blockquote>
</blockquote>
</div>

<div id="all-infos"><br />
<h3><?php _e('Get all infos about language', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_get_language_info($id = null); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$id:</strong> <i>(integer)</i> - Id of the language. If null return an object about language.</li>
	</ul>  
      <span class="return"><?php _e('Return:', 'ceceppaml') ?></span>
      <p style="padding-left: 20px">
	This function return an object and Its fields are:

	  <pre>
	  *) id           - id of language
	  *) cml_default  - 1 if it is the default language
	  *) cml_flag     - name of flag
	  *) cml_language - name of the language
	  *) cml_language_slug - slug of the language
	  *) cml_locale        - wordpress locale
	  *) cml_sort_id       - language order
	  </pre>
      </p>
    </blockquote>
</blockquote>
</div>

<div id="default-id"><br />
<h3><?php _e('Get the id of default language', 'ceceppaml') ?></h3>
  <blockquote>
    This function return the id of default language.
    <pre class="code">
      &lt;?php cml_get_default_language_id(); ?&gt;
    </pre>
  </blockquote>
</div>

<div id="byslug"><br />
<h3><?php _e('Get language by slug', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_get_language_by_slug( $slug ); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$locale:</strong> <i>(string)</i> - Slug of the language defined in the "Languages" tab of "Ceceppa Multilingua" page</li>
	</ul>  
      <span class="return"><?php _e('Return:', 'ceceppaml') ?></span>
      <p style="padding-left: 20px">
	This function return an object and Its fields are:

	  <pre>
	  *) id           - id of language
	  *) cml_default  - 1 if it is the default language
	  *) cml_flag     - name of flag
	  *) cml_language - name of the language
	  *) cml_language_slug - slug of the language
	  *) cml_locale        - wordpress locale
	  *) cml_sort_id       - language order
	  </pre>
      </p>
    </blockquote>
  <blockquote>
    <span class="example"><?php _e('Example:', 'ceceppaml') ?></span>
    <pre>
      &lt;?php;
	$lang = cml_get_language_by_slug( 'it' );
      ?&gt;
    </pre>
  </blockquote>
</blockquote>
</div>

<!-- FLAGS -->
<div id="show-all"><br />
<h3><?php _e('Show all available languages', 'ceceppaml') ?></h3>
<blockquote>
  This function return an &lt;ul&gt;...&lt;/ul&gt; list
  <pre class="code">
    &lt;?php cml_show_flags($show = "flag", $size = "tiny", $class_name = "cml_flags", $image_class = "", $echo = true, $linked = true); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$show:</strong> - indicates what to display. Possible values are:
	    <ul style="float: none; list-style: square;padding-left: 20px;">
	      <li><strong style="color: #00f">flag</strong> - will be shown only flags</li>
	      <li><strong style="color: #00f">text</strong> - will be shown only the names</li>
	      <li><strong style="color: #00f">both</strong> - will be shown only both, flags and names</li>
	    </ul>
	  </li>
	  <li><strong>$size:</strong> - Size of flags.
	    <ul style="float: none; list-style: square;padding-left: 20px;">
	      <li><strong style="color: #00f">tiny</strong> - 20x12</li>
	      <li><strong style="color: #00f">small</strong> - 80x55</li>
	    </ul>
	  </li>
	  <li><strong>$class_name:</strong> - classname to be assigned to the &lt;ul&gt;...&lt;/ul&gt; list.</li>
	  <li><strong>$image_class:</strong> - classname to be assigned to the &lt;img /&gt; element</li>
	  <li><strong>$echo:</strong> - Whether to echo the list or return it</li>
	  <li><strong>$linked:</strong> - If true allow you to switch between translations. If false the flag always link to homepage</li>
	</ul>  
    </blockquote>
</blockquote>
</div>

<div id="dropdown"><br />
<h3><?php _e('HTML dropdown list of languages', 'ceceppaml') ?></h3>
<blockquote>
  This function display HTML dropdown list of languages.
  <pre class="code">
    &lt;?php cml_dropdown_langs($class, $selected, $link = false, $none = false, $none_text = null, $none_id = "", $only_enabled = 1) ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$class:</strong> <i>(string)</i> - Class to be assigned to HTML element &lt;select&gt;</li>
	  <li><strong>$default:</strong> <i>(string)</i> - Id of selected language</li>
	  <li><strong>$link:</strong> <i>(string)</i> - Perform browser redirect on language change</li>
	  <li><strong>$none:</strong> <i>(boolean)</i> - Add empty option as first option</li>
	  <li><strong>$none_text:</strong> <i>(string)</i> - Text to add for empty option</li>
	  <li><strong>$none_id:</strong> <i>(integer)</i> - Id to be assigned to empty option</li>
	  <li><strong>$only_enabled:</strong> <i>(boolean)</i> - Show only enabled languages</li>
	</ul>  
    </blockquote>
</blockquote>
</div>


<div id="by-locale"><br />
<h3><?php _e('Get the path of a flag by language locale', 'ceceppaml') ?></h3>
<blockquote>
  This function return the path of the desired flag.
  <pre class="code">
    &lt;?php cml_get_flag($flag, $size = "tiny"); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
    <br />
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$flag</strong> <i>(string)</i>: - The locale of desired language. (Ex: en_US, it_IT...)</li>
	  <li>
	    <strong>$size</strong> <i>(string)</i>:  The size of flag. Possible values are:
	    <ul style="list-style: square; padding-left: 20px">
	      <li><?php _e('small', 'ceceppaml') ?> (32x33)</li>
	      <li><?php _e('tiny', 'ceceppaml') ?> (16x11)</li>
	    </ul>
	  </li>
	</ul>
      <br />
      <span class="return"><?php _e('Return:', 'ceceppaml') ?></span>
      <p style="padding-left: 20px">
	This function return a string containing the full path of the flag.
      </p>
    </blockquote>
</blockquote>
</div>

<div id="by-id"><br />
<h3><?php _e('Get the path of a flag by language id', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_get_flag_by_lang_id($id, $size = "tiny"); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
    <br />
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$id</strong> <i>(integer)</i>: - The id of language</li>
	  <li>
	    <strong>$size</strong> <i>(string)</i>:  The size of flag. Possible values are:
	    <ul style="list-style: square; padding-left: 20px">
	      <li><?php _e('small', 'ceceppaml') ?> (32x33)</li>
	      <li><?php _e('tiny', 'ceceppaml') ?> (16x11)</li>
	    </ul>
	  </li>
	</ul>
      <br />
    </blockquote>
</blockquote>
</div>

<div id="by-slug"><br />
<h3><?php _e('Get the path of a flag by language slug', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_get_flag_by_lang_slug($slug, $size = "tiny"); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
    <br />
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$slug</strong> <i>(string)</i>: - The slug of language. (Ex: en, it, ....)</li>
	  <li>
	    <strong>$size</strong> <i>(string)</i>:  The size of flag. Possible values are:
	    <ul style="list-style: square; padding-left: 20px">
	      <li><?php _e('small', 'ceceppaml') ?> (32x33)</li>
	      <li><?php _e('tiny', 'ceceppaml') ?> (16x11)</li>
	    </ul>
	  </li>
	</ul>
      <br />
    </blockquote>
</blockquote>
</div>

<div id="menu"><br />
<h3><?php _e('Get the menu associated to current language', 'ceceppaml') ?></h3>
<blockquote>
  If in "Appearance" -> "Menu" you have choosed different menu for each language, this function
  return the id of the menu to display in according to current language.
  
  The value can be passed to function "wp_nav_menu".
  <pre class="code">
    &lt;?php cml_get_menu(); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
  </blockquote>
  <blockquote>
    <span class="example"><?php _e('Example:', 'ceceppaml') ?></span>
    <pre>
      &lt;?php;
	$menu = cml_get_menu();
	wp_nav_menu(array('theme_location' => $menu));
      ?&gt;
    </pre>
  </blockquote>
</blockquote>
</div>


<div id="availables-only"><br />
<h3><?php _e('How to show flags only when translation exists', 'ceceppaml') ?></h3>
<blockquote>
  <?php _e('This shortcode return an &lt;ul&gt;...&lt;/ul&gt; list') ?>
  <pre class="code">
    &lt;?php cml_other_langs_available( $id ); ?&gt;
  </pre>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
      <ul style="float: none; list-style: circle;padding-left: 50px;">
	<li><strong>$id</strong> <i>(string)</i>: - The id of the post/page ( optional )</li>
      </ul>

</blockquote>
</div>


<div id="menuname"><br />
<h3><?php _e('Get the name of the menu associated to current language', 'ceceppaml') ?></h3>
<blockquote>
  If in "Appearance" -> "Menu" you have choosed different menu for each language, this function
  return the name of the menu to display in according to current language.
  <pre class="code">
    &lt;?php cml_get_menu_name(); ?&gt;
  </pre>
</blockquote>
</div>


<div id="getlang"><br />
<h3><?php _e('Get the language of page/post', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_get_language_of_post( $post_id ); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$post_id:</strong> <i>(integer)</i> - The ID of the post/page.</li>
	</ul>  
      <span class="return"><?php _e('Return:', 'ceceppaml') ?></span>
      <p style="padding-left: 20px">
	This function return an object and Its fields are:

	  <pre>
	  *) id           - id of language
	  *) cml_default  - 1 if it is the default language
	  *) cml_flag     - name of flag
	  *) cml_language - name of the language
	  *) cml_language_slug - slug of the language
	  *) cml_locale        - wordpress locale
	  *) cml_sort_id       - language order
	  </pre>
      </p>
    </blockquote>
  <blockquote>
    <span class="example"><?php _e('Example:', 'ceceppaml') ?></span>
    <pre>
      &lt;?php;
	$lang = cml_get_language_of_post( 1 );
      ?&gt;
    </pre>
  </blockquote>
</blockquote>
</div>

<div id="setlang"><br />
<h3><?php _e('Set the language of page/post', 'ceceppaml') ?></h3>
<blockquote>
  <pre class="code">
    &lt;?php cml_set_language_of_post( $post_id, $lang_id ); ?&gt;
  </pre>
  <blockquote>
    <span class="parameters"><?php _e('Parameters:', 'ceceppaml') ?></span>
	<ul style="float: none; list-style: circle;padding-left: 50px;">
	  <li><strong>$post_id:</strong> <i>(integer)</i> - The ID of the post/page</li>
	  <li><strong>$lang_id:</strong> <i>(integer)</i> - The id of the language</li>
	</ul>  
    </blockquote>
  <blockquote>
    <span class="example"><?php _e('Example:', 'ceceppaml') ?></span>
    <pre>
      &lt;?php;
	$lang = cml_get_language_by_slug( 'it' );
	
	cml_set_language_of_post( 1, $lang->id );
      ?&gt;
    </pre>
  </blockquote>
</blockquote>
</div>