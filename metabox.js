;(function ( $, window, document, undefined ) {
	$(document).ready( function() {

		var $typeSelect, basicFields, productFields, galleryFields, summaryFields, playerFields, hideFields, hideStuff, showEverything, postType;

		postType = twitterCardOptions.postType;

		$typeSelect = $('#' + postType + '_twitter_card_twitter_card_type');

		summaryFields = [ $('#' + postType + '-twitter-card-large-image') ];

		basicFields = [
			$('#' + postType + '_twitter_card_twitter_card_title').parent(),
			$('#' + postType + '_twitter_card_twitter_card_description').parent()
		];

		playerFields = [
			$('#' + postType + '-twitter-card-player-image'),
			$('#' + postType + '_twitter_card_twitter_card_player_url').parent(),
			$('#' + postType + '_twitter_card_twitter_card_player_width').parent(),
			$('#' + postType + '_twitter_card_twitter_card_player_height').parent(),
			$('#' + postType + '_twitter_card_twitter_card_player_stream').parent(),
			$('#' + postType + '_twitter_card_twitter_card_stream_content_type').parent()
		];

		productFields = [ $('#' + postType + '-twitter-card-product-image') ];
		for ( var i = 1; i <= 2; i++ ) {
			productFields.push( $('#' + postType + '_twitter_card_twitter_card_label'+i).parent() );
			productFields.push( $('#' + postType + '_twitter_card_twitter_card_data'+i).parent() );
		}

		galleryFields = [];
		for ( var i = 1; i <= 4; i++ ) {
			galleryFields.push( $('#' + postType + '-twitter-card-gallery-image-'+i) );
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
			hideFields(playerFields);
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
			if ( cardType === 'player' ) {
				showFields(playerFields);
			}
		}

		showByType();
		$typeSelect.on( 'change', showByType );

	} );
})( jQuery, window, document );