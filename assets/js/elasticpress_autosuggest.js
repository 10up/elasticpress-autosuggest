/*! ElasticPress Autosuggest - v0.1.0
 * http://github.com/10up/ElasticPress-Autosuggest
 * Copyright (c) 2015; * Licensed GPLv2+ */
( function( window, undefined ) {
	'use strict';

	var document = window.document;

	jQuery( document ).ready( function( $ ) {
		var $epInput       = $( 'form.searchform input#s' );
		var $epAutosuggest = $( '<div class="ep-autosuggest"><ul class="autosuggest-list"></ul></div>' );

		/**
		 * Build the auto-suggest container
		 */
		$epInput.each( function( key, input ) {
			var $epContainer = $( '<div class="ep-autosuggest-container"></div>' );
			var $input = $( input );

			// Disable autocomplete
			$input.attr( 'autocomplete', 'off' );

			$epContainer.insertAfter( $input );
			var $epLabel = $input.siblings( 'label' );
			$input
				.closest( 'form' )
				.find( '.ep-autosuggest-container' )
				.append( $epLabel )
				.append( $input );

			$epAutosuggest.clone().insertAfter( $input );

			$input.trigger( 'elasticpress.input.moved' );
		} );

		var esServer = window.location.host;
		var $dataESHost = $epInput.data( 'es-host' );
		if ( $dataESHost !== undefined && $dataESHost !== null && $dataESHost.length > 0 ) {
			esServer = $epInput.data( 'es-host' );
		}

		var esHost = window.location.protocol + "//" + esServer + '/es-search/';

		/**
		 * Build the search query from the search text
		 *
		 * @param searchText
		 * @returns object
		 */
		function buildSearchQuery( searchText, postType ) {
			if ( postType === 'all' || typeof( postType ) === 'undefined' || postType === '' ) {
				postType = 'all';
			}
			// TODO: check comma separated
			var query =
			{
				"post-suggest": {
					"text": searchText,
					"completion": {
						"field": "term_suggest_" + postType
					}
				}
			};

			return query;
		}

		/**
		 * Build the ajax request
		 *
		 * @param query
		 * @returns AJAX object request
		 */
		function esSearch( query ) {
			// @todo fix this for multi-different search boxes on the same page
			var url = esHost + '_suggest';

			// Fixes <=IE9 jQuery AJAX bug that prevents ajax request from firing
			jQuery.support.cors = true;

			var request;
			request = $.ajax( {
				"url": url,
				"type": 'POST',
				"dataType": 'json',
				"crossDomain": true,
				"data": window.JSON.stringify( query )
			} );

			return request;
		}

		/**
		 * Simple throttling function for waiting a set amount of time after the last keypress
		 * So we don't overload the server with too many requests at once
		 *
		 * @param fn
		 * @param delay
		 * @returns {Function}
		 */
		function debounce(fn, delay) {
			var timer = null;
			return function () {
				var context = this, args = arguments;
				window.clearTimeout(timer);
				timer = window.setTimeout( function () {
					fn.apply(context, args);
				}, delay);
			};
		}

		$epAutosuggest.css( {
			'top': $epInput.outerHeight() - 1,
			'background-color': $epInput.css( 'background-color' )
		} );

		/**
		 * Update the auto suggest box with new options or hide if none
		 *
		 * @param options
		 * @return void
		 */
		function updateAutosuggestBox( options, $localInput ) {
			var i;
			var $localESContainer = $localInput.closest( '.ep-autosuggest-container' ).find( '.ep-autosuggest' );

			var $localSuggestList = $localESContainer.find( '.autosuggest-list' );
			$localSuggestList.empty();

			var itemString;

			// Unbind potentially previously set items
			$( '.autosuggest-item' ).unbind();

			if ( options.length > 0 ) {
				$localESContainer.show();
			} else {
				$localESContainer.hide();
			}

			for ( i = 0; i < options.length; ++i ) {
				var item = options[i].text.toLowerCase();
				itemString = '<li><span class="autosuggest-item" data-search="' + item + '">' + item + '</span></li>';
				$( itemString ).appendTo( $localSuggestList );
			}

			// Bind items to auto-fill search box and submit form
			$( '.autosuggest-item' ).on( 'click', function( event ) {
				selectAutosuggestItem( $localInput, event.srcElement.innerText );
				submitSearchForm( $localInput );
			} );

			$localInput.unbind( 'keydown' );
			// Bind the input for up and down navigation between autosuggest items
			$localInput.on( 'keydown', function( event ) {
				if ( event.keyCode === 38 || event.keyCode === 40 || event.keyCode === 13 ) {
					var $results = $localInput.closest( '.ep-autosuggest-container' ).find( '.autosuggest-list li' );
					var $current = $results.filter( '.selected' );
					var $next;

					switch ( event.keyCode ) {
						case 38: // Up
							$next = $current.prev();
							break;
						case 40: // Down
							if ( ! $results.hasClass( 'selected' ) ) {
								$results.first().addClass( 'selected' );
							}
							$next = $current.next();
							break;
						case 13: // Enter
							if ( $results.hasClass( 'selected' ) ) {
								selectAutosuggestItem( $localInput, $current.find('span').text() );
								submitSearchForm( $localInput );
								return false;
							}
							break;
					}

					// only check next element if up and down key pressed
					if ( $next.is( 'li' ) ) {
						$current.removeClass( 'selected' );
						$next.addClass( 'selected' );
					}

					// keep cursor from heading back to the beginning in the input
					if( event.keyCode === 38 ) {
						return false;
					}

					return;
				}

			} );
		}

		/**
		 * Singular bindings for up and down to prevent normal actions so we can use them to navigate
		 * our autosuggest list
		 * Bind the escape key to close the autosuggest box
		 */
		$( $epInput ).each( function( key, value ) {
			$( value ).bind( 'keyup keydown keypress', function( event ) {
				if ( event.keyCode === 38 || event.keyCode === 40) {
					event.preventDefault();
				}
				if ( event.keyCode === 27 ) {
					hideAutosuggestBox();
				}
			} );
		} );

		/**
		 * Take selected item and fill the search input
		 * @param event
		 */
		function selectAutosuggestItem( $localInput, text ) {
			$localInput.val( text );
		}

		/**
		 * Submit the search form
		 * @param object $localInput
		 */
		function submitSearchForm( $localInput ) {
			$localInput.closest( 'form' ).submit();
		}

		/**
		 * Hide the auto suggest box
		 *
		 * @return void
		 */
		function hideAutosuggestBox() {
			$( '.autosuggest-list' ).empty();
			$( '.ep-autosuggest' ).hide();
		}

		/**
		 * Listen for any keyup events, throttle them to a min threshold of time
		 * and then send them for a query to the Elasticsearch server
		 *
		 */
		$epInput.each( function( key, localInput ) {
			var $localInput = $( localInput );
			$localInput.on( 'keyup', debounce( function( event ) {
				if ( event.keyCode === 38 || event.keyCode === 40 || event.keyCode === 13 || event.keyCode === 27 ) {
					return;
				}

				var val = $localInput.val();
				var query;
				var request;
				var postType = $localInput.data( 'es-post-type' );

				if ( val.length >= 2 ) {
					query = buildSearchQuery( val, postType );
					request = esSearch( query );
					request.done( function( response ) {
						if ( response._shards.successful > 0 ) {
							var options = response['post-suggest'][0]['options'];
							if ( 0 === options.length ) {
								hideAutosuggestBox();
							} else {
								updateAutosuggestBox( options, $localInput );
							}
						} else {
							hideAutosuggestBox();
						}
					} );
				} else if ( 0 === val.length ) {
					hideAutosuggestBox();
				}
			}, 100 ) );
		} );


	} );

} )( this );