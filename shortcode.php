<?php
/**
  * Shortcode per tradurre stringhe, ex. titoli o contenuti dei widget
  * Nel caso la lingua impostata tramite il parametro LANG, non esiste tra le scelte, viene
  * restituita la stringa associata alla lingua di default
  *
  *  cml_text - serve a tradurre stringhe in verie lingue.
  *      Utilizzo: 
  *       [cml_text lingua1="valore" lingua2="valore" ...]
  *
  *      Esempio: 
  *       [cml_text it="Stringa in italiano" en="String in English" epo="Teksto en Esperanto"]
  *
  *  cml_shortcode - serve a eseguire un'altro shortcode e passargli parametri in base alla lingua
  *    Utilizzo:
  *      [cml_shortcode shortcode="[shortcode]" [parameters]="[valore]" [languages]="[elenco_valori]"
  *        
  *      @shortcode - nome dello shortcode da eseguire
  *      @params - parametri "fissi" da passare allo shortcode
  *      @[languages] - serve a specificare valori per ogni lingua
  *
  *    Esempio:
  *      [amrp parameters="limit=4" it="cats=28" epo="1" en="2"]
  *
  *
  *  cml_flag - serve a visualizzare l'icona e/o il testo della lingua specificata
  *      Utilizzo:
  *       [cml_flag lang="it" show="flag|text"]
  *          
  *       @lang - slug della lingua da visualizzare
  *       @show - (facolativo) serve specificare se vogliamo che la funzione restituisca la bandiera della lingua (flag) 
  *               oppure vogliamo il nome della lingua.
  *               Se non è specificato restituisce entrambi
  *
  *  cml_show_availables_lang - Serve a visualizzare l'elenco delle lingue in cui è disponibile la catagoria/pagina/articolo
  *
  *  cml_show_flags - visualizza le lingue disponibile con le relative bandiere
  *      Utilizzo:
  *        [cml_show_flags show="flag" size="tiny"]
  *
  *        @show - indica se visualizzare solo la bandiere o anche il nome della lingua. I valori possibili sono:
  *                "flag" - viene visualizzata solo la bandiera
  *                ""     - viene visualizzato anche il nome della lingua all'interno della lista
  *        @size - dimensione dell'immagine. I valori possibili sono
  *                "tiny" = 20x15
  *                "small" = 80x55
  *        
  */
add_shortcode("cml_text", 'cml_shortcode_text');
add_shortcode("cml_shortcode", 'cml_do_shortcode');
add_shortcode('cml_show_availables_langs', 'cml_show_availables_langs');
add_shortcode('cml_show_flags', 'cml_shortcode_show_flags');

function cml_shortcode_text($attrs) {
  global $wpCeceppaML;

  $string = $attrs[$wpCeceppaML->get_current_lang()];
  if(!empty($string))
    return $string;
  else
    return $attrs[$wpCeceppaML->get_default_language_slug()];
}

function cml_do_shortcode($attrs) {
  global $wpCeceppaML;

  $shortcode = $attrs['shortcode'];
  $params = @$attrs['params'];
  $lang = $attrs[$wpCeceppaML->get_current_lang()];

  return do_shortcode("[$shortcode $params $lang]");
}

function cml_show_availables_langs($attrs) {
  global $wpdb, $wpCeceppaML;

  $notice = isset($attrs['notice']) ? $attrs['notice'] : true;
  $class = $attrs['class'];

  $langs = cml_get_languages();
  $l_id = $wpCeceppaML->get_current_lang_id();
  $cat_id = $wpCeceppaML->get_category_id(single_cat_title("", false));

  $r = "<ul class='cml_flags $class'>";
  foreach($langs as $lang) {

    if(is_category()) $link = cml_get_linked_cat($l_id, $lang, $cat_id, null);
    if(is_single() || is_page()) $link = cml_get_linked_post($l_id, $lang, get_the_ID(), null);
    //if(is_page()) $link = cml_get_linked_post($l_id, $lang, get_the_ID(), null);

    if(!empty($link)) {
      $link = (is_category()) ? get_category_link($link) : get_permalink($link);

      $title = ($notice && $l_id != $lang->id) ? cml_get_notice_by_lang_id($lang->id) : $lang->cml_language;
      $r .= "<li><a href='$link'><img src='" . cml_get_flag_by_lang_id($lang->id, 'small') . "' title=\"$title\" class=\"tipsy-me\"/></a></li>";
    }
  }

  $r .= "</ul>";

  return $r;
}

function cml_shortcode_show_flags($attrs) {
  $show = $attrs['show'];
  $flag = $attrs['size'];

  return cml_show_flags($show, $flag, false);
}

?>