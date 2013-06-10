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
?>
<!-- Elenco articoli -->
    <div class="wrap">
    <h2><?php _e('Elenco articoli:', 'ceceppaml') ?></h2>
    <table style="width: 100%;" class="ceceppaml_posts">
    <tbody>
    <tr style="border-bottom: 1px solid #323232;">
      <th style="width:70%;"><?php _e('Titolo', 'ceceppaml') ?></th>
      <?php
	  $langs = cml_get_languages();
	  
	  foreach($langs as $lang) {
	    echo "<th><img src='" . cml_get_flag($lang->cml_flag) . "'/>&nbsp;$lang->cml_language</th>";
	  }
	 ?>
    </tr>
    <?php
      global $wpCeceppaML;

      //$posts = new WP_Query('order=ASC;post_per_page=-1,numberposts=10,status=publish');
			$posts = get_posts(array('order' => 'ASC',
                               'orderby' => 'title',
															 'numberposts' => -1,
															 'status' => 'publish'));
      $default_id = cml_get_default_language_id();

      //while ($posts->have_posts()) : $posts->the_post(); 
			foreach($posts as $post) : setup_postdata($post);
			/*
			 * Se l'articolo corrente è una traduzione non lo visualizzo
			 */
      $results = $wpdb->get_results(sprintf("SELECT * FROM %s WHERE cml_post_id_1 = %d",
                                      CECEPPA_ML_POSTS, $post->ID));

      if(empty($results)) :
        echo "<tr><td>" . get_the_title($post->ID) . "</td>";

        $post_id = $wpCeceppaML->get_language_id_by_post_id($post->ID);

        //Controllo per ogni lingua impostata se è disponibile l'articolo
        foreach($langs as $lang) :
					$link = cml_get_linked_post($post_id, null, $post->ID, $lang->id);

          //Esiste una traduzione per questo articolo?
          if(!empty($link)) {
          ?>
            <td>
              <center>
                <a href="<?php echo get_edit_post_link($link) ?>">
                  <img src="<?php echo cml_get_flag($lang->cml_flag) ?>" title="<?php _e('Modifica articolo', 'ceceppaml') ?>" />
                </a>
              </center>
            </td>
          <?php
          } else {
          ?>
            <td>
              <center>
                <a href="<?php bloginfo("url") ?>/wp-admin/post-new.php?link-to=<?php echo $post->ID ?>">
                  <img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/add.png" title="<?php _e('Traduci articolo', 'ceceppaml') ?>" />
                </a>
              </center>
            </td>          
          <?php
          }
        endforeach;

        echo "</tr>";
      endif;
			endforeach;
      //endwhile;
      ?>
    </tbody>
    </table>
    </div>