<?php
/**
 * @param $lang_id - id della lingua corrente
 * @param $result - result della query (facoltativo)
 * @param $post_id - id del post
 * @param $browser_lang - id della lingua per la quale si cerca l'articolo collegato
 * 
 *      [ A ] <--> [ B ] <--> [ C ]
 *
 * Ipotesi 1: Stò visualizzando l'articolo B, ovvero quello della lingua predefinita.
 *	Da B posso ricavare il link della lingua che voglio tramite l'identificativo della stessa ($result->id)
 *
 * Ipotesi 2: Da A voglio ricavare i link di B e C
 *	Dato che ogni articolo può essere collegato a uno soltanto e che gli articoli collegati li trovo nella prima colonna della tabella (...)_id_1
 *	Per ottenere il link di B basta che recupero l'id_2 utilizzando come condizione l'id_1
 *	Se da A voglio recuperare l'articolo C devo passare prima per A e poi recuperare l'articolo C abbinato ad A
 *
 * Caso 2:
 *	[ A ] <--> [ C ]
 *
 *
 */
function cml_get_linked_post($lang_id, $result, $post_id, $browser_lang = null) {
  global $wpdb;

  $link = null;

  if(empty($result)) $result = $wpdb->get_row(sprintf("SELECT * FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $browser_lang));

  //Non confronto la lingua con se stessa :D
  if($result->id != $lang_id) {
      /*
	* Devo cercare sia in cml_post_id_1 che in cml_post_id_2, xkè posso avere
	* degli articoli collegati tra di loro, ma non a quella predefinita, e considerando
	* solo cml_post_id_1 perderei questa informazione :(
	*/
      $query = sprintf("SELECT *, cml_post_id_2 as post_id FROM %s WHERE (cml_post_id_1 = %d OR cml_post_id_2 = %d) AND (cml_post_id_1 > 0 AND cml_post_id_2 > 0)",
			CECEPPA_ML_POSTS, $post_id, $post_id);
      $new_id = $wpdb->get_row($query);

      if(!empty($new_id)) :
	if($new_id->cml_post_lang_2 != $result->id && $new_id->post_id > 0) {
	  //Se la lingua che ho recuperato è diversa da quella attuale verifico se c'è un altro
	  //post in un'altra lingua collegato a questo
	  $query = sprintf("SELECT *, cml_post_id_1 as post_id FROM %s WHERE cml_post_id_2 = %d AND cml_post_lang_1 = %d",
			    CECEPPA_ML_POSTS, $new_id->post_id, $result->id);

	  $new_id = $wpdb->get_row($query);
	} else {
	  $new_id = $new_id->post_id;
	}
      endif;

      if(is_object($new_id)) $new_id = $new_id->post_id;
  } else
    $link = $post_id;

  if(!empty($new_id))
    $link = $new_id;

  return $link;
}


/**
 * http://www.cult-f.net/detect-crawlers-with-php/
 *
 * !!!ATTENZIONE!!! NON SO SE E' NECESSARIA QUESTA FUNZIONE, NEL CASO SI RIVELASSE INUTILE, VERRA' RIMOSSA :)
 * Questa funzione server per evitare di reindirizzare o nascondere i post nella lingua differente
 * da quella del browser se schi stà visitando il sito è un crawler, al fine di permettere l'indicizzazione di tutti
 * gli articoli
 *
 */
function isCrawler()
{
    $USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

    // to get crawlers string used in function uncomment it
    // it is better to save it in string than use implode every time
    // global $crawlers
    // $crawlers_agents = implode('|',$crawlers);
    $crawlers_agents = 'Google|msnbot|Rambler|Yahoo|AbachoBOT|accoona|AcioRobot|ASPSeek|CocoCrawler|Dumbot|FAST-WebCrawler|GeonaBot|Gigabot|Lycos|MSRBOT|Scooter|AltaVista|IDBot|eStyle|Scrubby';
 
    if ( strpos($crawlers_agents , $USER_AGENT) === false )
       return false;
    // crawler detected
    // you can use it to return its name
    /*
    else {
       return array_search($USER_AGENT, $crawlers);
    }
    */
    return true;
}

?>