<?php
/*
 * In questo file memorizzo tutte le funzioni "deprecate", ovvero tutte le fuzioni che sostituirò
 * con altre più performanti.
 * Le conservo qui perché queste sono più "testate", e le cancellerò solo una volta che la versione più performante
 * è funzionante al 100%
 */
function cml_deprecated_hide_translations($wp_query) {
  global $wpdb, $wpCeceppaML;

  if($wp_query != null && (is_page() || is_single() || isCrawler())) return;
  if(!is_admin()) $wpCeceppaML->update_current_lang();

  if(empty($wpCeceppaML->_exclude_posts)) :
      /*
	* A cosa servono cml_fake_id_1 e cml_fake_id_2?
	* Per poter visualizzare un articolo in tutte le lingue, basta che non gli assegno nessuna lingua.
	* Se scrivo un articolo e non gli assegno nessuna lingua, e poi per tale articolo ne creo una traduzione, tale post non era più "visibile"
	* nell'archivio delle categorie. 
	* In quanto il 4 if che segue lo marcava come "post__not_in". Dato che i post senza lingua vengono contrassegnati con "0" con una case when
	* assegno un id "temporaneo" a tale articolo, così da ingannare il plugin ;)
	* Non posso rimuovere il 4 if perché l'esclusione delle traduzioni si basa sull'idea di nascondere tutti i post che sono traduzioni della lingua
	* corrente.... l'idea fallisce nel momento in cui ho, per esempio, 3 lingue e traduco un articolo per solo 2 di queste lingue, es. per la lingua 1 e 2.
	* Ora se voglio visualizzare gli articoli della lingua 3 mi ritroverei con l'articolo tradotto precedentemente, sia per la lingua 1 che 2, in quanto
	* non esiste una traduzione nella lingua 3... e non posso nasconderla :(
	* A questo serve il 4 if :)
	*/
      $query = sprintf("SELECT *, case when cml_post_lang_1 > 0 then cml_post_lang_1 else  %d end as cml_fake_id_1, case when cml_post_lang_2 > 0 then cml_post_lang_2 else  %d end as cml_fake_id_2 FROM %s", $wpCeceppaML->get_current_lang_id(false), $wpCeceppaML->get_current_lang_id(false), CECEPPA_ML_POSTS);
      $results = $wpdb->get_results($query);

      foreach($results as $result) :
	  if($result->cml_fake_id_1 != $result->cml_fake_id_2) :
	    if($result->cml_post_id_2 > 0 && $result->cml_fake_id_1 == $wpCeceppaML->get_current_lang_id(false)) $posts[] = $result->cml_post_id_2;
	    if($result->cml_post_id_1 > 0 && $result->cml_fake_id_2 == $wpCeceppaML->get_current_lang_id(false)) $posts[] = $result->cml_post_id_1;
	  endif;

	  if(($result->cml_post_id_2 > 0 && $result->cml_post_id_1 > 0) &&
		$result->cml_fake_id_1 != $wpCeceppaML->get_current_lang_id(false) && $result->cml_fake_id_2 != $wpCeceppaML->get_current_lang_id(false)) :
		
		//Rimuovo il secondo xkè sicuramente è una traduzione
		$posts[] = $result->cml_post_id_2;
		
		//Per il primo controllo se è una traduzione di un articolo in questa lingua
		$nid = cml_get_linked_post($result->cml_fake_id_1, null, $result->cml_post_id_1, $wpCeceppaML->get_current_lang_id(false));
		if(!empty($nid)) $posts[] = $result->cml_post_id_1;
	  endif;

	  //Può capitare che ho n (> 2) lingue e alcuni articoli non sono tradotti in queste, allora nascondo gli articoli
	  //per evitare che vengano visualizzati entrambi.
	  if($result->cml_fake_id_1 != $wpCeceppaML->get_current_lang_id(false) && $result->cml_fake_id_2 != $wpCeceppaML->get_current_lang_id(false)):
	    //Nascondo l'id 2 che è sicuramente una traduzione...
	    $posts[] = $result->cml_post_id_2;
	  endif;
      endforeach;

      $wpCeceppaML->_exclude_posts = !empty($posts) ? $posts : array();

      if(is_tag()) :
	/*
	  Può capitare che l'utente invece di tradurre un tag ne usa uno nuovo per l'articolo. In questo caso
	  aggiungendo all'url ?lang=##, viene visualizzato il messaggio 404 se per quella lingua non c'è nessun
	  articolo con quel tag, il ché può essere un po' noioso.
	  Dagli articoli da escludere rimuovo tutti quelli che non hanno quel tag. Così se assegno 2 tag diversi
	  all'articolo e alla sua traduzione non mi ritrovo con un 404.
	  Il metodo, a causa dei vari cicli da eseguire, probabilmente porterà dei rallentamenti nel caricamento delle pagine
	  dei tag, però è anche l'unico modo di evitare pagine 404 se all'utente piace assegnare tag diversi invece di tradurli...
	*/
	$tag_name = $wp_query->query_vars['tag'];
	$i = 0;

	foreach($wpCeceppaML->_exclude_posts as $id) :
	  $tags = wp_get_post_tags($id);
	  $lang_id = $wpCeceppaML->get_language_id_by_post_id($id);

	  foreach($tags as $tag) :
	    if($tag->name == $tag_name && $lang_id != $wpCeceppaML->get_current_lang_id(false)) :

	      //Controllo che per questa articolo non esista nessuna traduzione nella lingua corrente con la stessa categoria
	      $nid = cml_get_linked_post($lang_id, null, $id, $wpCeceppaML->get_current_lang_id(false));
	      echo $nid . ", " . $id;
	      if(!empty($nid)) :
		//Verifico le categorie dell'articolo collegato
		$_tags = wp_get_post_tags($nid);
		$found = false;
		foreach($_tags as $_tag) :
		  if($_tag->name == $tag_name) :
		    $found = true;
		    break;
		  endif;
		endforeach;
		
		if(!$found) :
		  unset($wpCeceppaML->_exclude_posts[$i]);
		  break;
		endif;
	      endif;
	    endif;
	  endforeach;
	  
	  $i++;
	endforeach;
      endif;
  endif;

  if($wp_query != null && is_object($wp_query)) :
    $wp_query->query_vars['post__not_in'] = $wpCeceppaML->_exclude_posts;

    return;
  endif;
//        set_query_var('post__not_in', $wpCeceppaML->_exclude_posts);

  return $wpCeceppaML->_exclude_posts;
}

  function cml_deprecated_get_language_posts_id($lang) {
    global $wpdb;

    /**
     * Al fine di velocizzare il processo evito di eseguire la stessa query
     * se la lingua non è cambiata
     */
    if(!isset($this->_language_lang_id) || $lang != $this->_language_lang_id) :
      $query = sprintf("SELECT *, case when cml_post_lang_1 > 0 then cml_post_lang_1 else %d end as cml_fake_id_1, case when cml_post_lang_2 > 0 then cml_post_lang_2 else %d end as cml_fake_id_2 FROM %s", $lang, $lang, CECEPPA_ML_POSTS);
      $results = $wpdb->get_results($query);

      $posts = array();
      foreach($results as $result) :
	  if(($result->cml_fake_id_1 != $result->cml_fake_id_2) || 
	     ($result->cml_post_id_2 == 0)) :
	    if($result->cml_fake_id_1 == $lang) $posts[] = $result->cml_post_id_1;
	    if($result->cml_fake_id_2 == $lang) $posts[] = $result->cml_post_id_2;
	  endif;
      endforeach;

      //Se ad un posto non ho assegnato nessuna lingua rischio di "escluderlo", quini aggiungo all'elenco tutti i post figli di nessuno :)
      $query = sprintf("SELECT ID from $wpdb->posts WHERE id NOT IN (SELECT cml_post_id_1 FROM %s)", CECEPPA_ML_POSTS);
      $results = $wpdb->get_results($query);
      foreach($results as $result) :
	$posts[] = $result->ID;
      endforeach;

      /*
       * Rimuovo dall'elenco tutti i post tutti i post a cui è associata una traduzione.
       * Se installo il plugin su un sito esistente non posso obbligare l'autore a impostare la lingua
       * a tutti gli articoli già esistenti.
       * Però devo fare anche in modo che articoli senza lingua non compaiano se in queste lingue esistono delle traduzioni
       */
      $query = sprintf("SELECT ID from $wpdb->posts WHERE id IN (SELECT cml_post_id_2 FROM %s)", CECEPPA_ML_POSTS);
      $results = $wpdb->get_results($query);
      foreach($results as $result) :
	if(in_array($result->ID, $posts)) :
	  $key = array_search($result->ID, $posts);
	  unset($posts[$key]);
	endif;
      endforeach;

      $this->_language_posts_id = $posts;
      $this->_language_lang_id = $lang;
    endif;

    return $this->_language_posts_id;
  }
  
function cml_deprecated_hide_translations_for_tags($wp_query) {
  global $wpCeceppaML, $wpdb;

  /*
    Può capitare che l'utente invece di tradurre un tag ne usa uno nuovo per l'articolo. In questo caso
    aggiungendo all'url ?lang=##, viene visualizzato il messaggio 404 se per quella lingua non c'è nessun
    articolo con quel tag, il ché può essere un po' noioso.
    Dagli articoli da escludere rimuovo tutti quelli che non hanno quel tag. Così se assegno 2 tag diversi
    all'articolo e alla sua traduzione non mi ritrovo con un 404.
    Il metodo, a causa dei vari cicli da eseguire, probabilmente porterà dei rallentamenti nel caricamento delle pagine
    dei tag, però è anche l'unico modo di evitare pagine 404 se all'utente piace assegnare tag diversi invece di tradurli...
  */
  $tag_name = $wp_query->query_vars['tag'];
  $i = 0;

  foreach($wpCeceppaML->_hide_posts as $id) :
    $tags = wp_get_post_tags($id);
    $lang_id = $wpCeceppaML->get_language_id_by_post_id($id);

    foreach($tags as $tag) :
      if($tag->name == $tag_name && $lang_id != $wpCeceppaML->get_current_lang_id(false)) :

	//Controllo che per questa articolo non esista nessuna traduzione nella lingua corrente con la stessa categoria
	$nid = cml_get_linked_post($lang_id, null, $id, $wpCeceppaML->get_current_lang_id(false));
	echo $nid . ", " . $id;
	if(!empty($nid)) :
	  //Verifico le categorie dell'articolo collegato
	  $_tags = wp_get_post_tags($nid);
	  $found = false;
	  foreach($_tags as $_tag) :
	    if($_tag->name == $tag_name) :
	      $found = true;
	      break;
	    endif;
	  endforeach;
	  
	  if(!$found) :
	    unset($wpCeceppaML->_hide_posts[$i]);
	    break;
	  endif;
	endif;
      endif;
    endforeach;
    
    $i++;
  endforeach;
}
?>
