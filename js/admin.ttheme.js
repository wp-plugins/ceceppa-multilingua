jQuery( document ).ready( function( $ ) {
  //Update info about untranslated rows
  if( $( '.ceceppaml-theme-translations' ).length > 0 ) updateInfo();

  /*
   * file .mo is generated after "load_plugin_textdomain", so I send data via ajax
   * and when done refresh page :)
   */
  $( 'body' ).on( 'submit', '.ceceppa-form-translations', function() {
    $form = $( this );
    $form.find( '.spinner' ).fadeIn();

    $.ajax( {
      type: 'POST',
      url: ajaxurl,
      data: $( this ).serialize(),
      success: function( data ) {
        $form.find( '.cml-submit-button > .wpspinner > .spinner' ).fadeOut();

        console.log( data );
        $data = null;

        if ( data == "-1" ) {
          console.log( "Failed" );
          alert( 'Failed!!!' );
          return;
        }

        try {
          $data = $.parseJSON( data );
        } catch(e) {
          return;
        }
        
        if ( $data == null) return;
        
        if ( $data.url ) window.location = $data.url;
      },
      error: function (xhr, ajaxOptions, thrownError) {
        //console.log( xhr.status );
        //console.log( thrownError );
      }
    });

    return false;
  } );
  
  jQuery( 'body' ).on( 'change keyup keypress', '.search input.s', function() {
    $table = $( 'table.ceceppaml-theme-translations' );
    $val = $( this ).val();

    $table.find( 'tr > td.item' ).each( function() {
      html = $( this ).html();
      
      var display = html.toLowerCase().indexOf( $val );

      $( this ).parent().css( "display", ( display >= 0 ) ? "table-row" : "none" );
    });
  });

  $('table.ceceppaml-theme-translations tr.row-domain').click( function() {
    $( this ).removeClass( 'row-open' );

    $next = $( this ).next();
    $next.toggle();
    if( $next.is(":visible") ) $( this ).addClass( 'row-open' );
  });
});


function showStrings( id, what ) {
  if( what == undefined ) {
    what = ".row-domain";
  } else {
    what = ".string-" + what;
  }

  jQuery( 'h2.tab-strings a' ).removeClass( 'nav-tab-active' );
  jQuery( jQuery( 'h2.tab-strings a' ).get( id ) ).addClass( 'nav-tab-active' );

  console.log( what );
  jQuery( 'table.ceceppaml-theme-translations tbody tr' + what ).show();
  
  if( what != undefined || what != "" ) {
    jQuery( 'table.ceceppaml-theme-translations tbody tr' ).not( what ).hide();
  }
}

function updateInfo() {
  $a = jQuery( '.tab-strings a' );
  
  $a.first().find( 'span' ).html( " (" + jQuery( '.row-domain' ).length + ")" );
  jQuery( $a.get( 1 ) ).find( 'span' ).html( " (" + jQuery( '.string-to-translate' ).length + ")" );
  jQuery( $a.get( 2 ) ).find( 'span' ).html( " (" + jQuery( '.string-incomplete' ).length + ")" );
  $a.last().find( 'span' ).html( " (" + jQuery( '.string-translated' ).length + ")" );
}