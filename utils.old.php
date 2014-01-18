<?php
/*  Copyright 2013  Alessandro Senese (email : senesealessandro@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

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
function cml_old_get_linked_post($lang_id, $result, $post_id, $browser_lang = null) {
  global $wpdb;

  $link = null;

  if(empty($result)) :
    if(empty($browser_lang)) $browser_lang = cml_get_current_language_id();
    $result = $wpdb->get_row(sprintf("SELECT * FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $browser_lang));
  endif;

  //Non confronto la lingua con se stessa :D
  if( is_object( $result ) && $result->id != $lang_id ) {
      /*
	* Devo cercare sia in cml_post_id_1 che in cml_post_id_2, xkè posso avere
	* degli articoli collegati tra di loro, ma non a quella predefinita, e considerando
	* solo cml_post_id_1 perderei questa informazione :(
	*/
      $query = sprintf("SELECT *, cml_post_id_2 as post_id FROM %s WHERE (cml_post_id_1 = %d OR cml_post_id_2 = %d) AND (cml_post_id_1 > 0 AND cml_post_id_2 > 0)",
			CECEPPA_ML_POSTS, $post_id, $post_id);
      $new_id = $wpdb->get_row( $query );

      if( ! empty( $new_id ) ) :
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

function cml_old_get_linked_posts( $id = null ) {
  global $wpCeceppaML;

  if( empty( $id ) ) $id = get_the_ID();
  if( ! $wpCeceppaML->has_translations( $id ) ) return array();

  $lid = $wpCeceppaML->get_language_id_by_post_id( get_the_ID() );
  $langs = cml_get_languages();

  $ids = array(); $posts = array();
  foreach( $langs as $lang ) {
    $nid = cml_get_linked_post( $lid, $lang, $id, $lang->id );

    if( ! empty( $nid ) ) {
      $ids[ $lang->cml_language_slug ] = $nid;
      $posts[] = $nid;
    }
  }

  $ids[ "indexes" ] = $posts;
  $ids[ "posts" ] = join( ",", $posts );
  return $ids;
}
?>