;(function ( $, window, document, undefined ) {
	$(document).ready( function() {

		var $typeSelect, basicFields, productFields, galleryFields, summaryFields, hideFields, hideStuff, showEverything;

		$typeSelect = $('#post_twitter_card_twitter_card_type');

		summaryFields = [ $('#post-twitter-card-large-image') ];

		basicFields = [
			$('#post_twitter_card_twitter_card_title').parent(),
			$('#post_twitter_card_twitter_card_description').parent()
		];

		productFields = [ $('#post-twitter-card-product-image') ];
		for ( var i = 1; i <= 2; i++ ) {
			productFields.push( $('#post_twitter_card_twitter_card_label'+i).parent() );
			productFields.push( $('#post_twitter_card_twitter_card_data'+i).parent() );
		}

		galleryFields = [];
		for ( var i = 1; i <= 4; i++ ) {
			galleryFields.push( $('#post-twitter-card-gallery-image-'+i) );
		}

		hideFields = function( fields ) {
			$.each( fields, function( i, $v ) {
				$v.hide();
			} );
		}

		showFields = function( fields ) {
			$.each( fields, function( i, $v ) {
				$v.show();
			} );
		}

		hideEverything = function() {
			hideFields(galleryFields);
			hideFields(summaryFields);
			hideFields(basicFields);
			hideFields(productFields);
		}

		showByType = function () {
			hideEverything();
			var cardType = $typeSelect.val();
			if ( cardType != '' ) {
				showFields(basicFields);
			}
			if ( cardType === 'gallery' ) {
				showFields(galleryFields);
			}
			if ( cardType === 'summary_large_image' ) {
				showFields(summaryFields);
			}
			if ( cardType === 'product' ) {
				showFields(productFields);
			}
		}

		showByType();
		$typeSelect.on( 'change', showByType );

	} );
})( jQuery, window, document );