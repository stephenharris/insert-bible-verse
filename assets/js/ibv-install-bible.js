/*! Ebenezer - v0.1.0
 * http://wordpress.org/plugins
 * Copyright (c) 2014; * Licensed GPLv2+ */
( function( window, undefined ) {
	'use strict';
	
	var bibleverse = {
		
		batch: 0,
		
		percent: 0,
		
		el: {},
		
		start_time: false,
		
		data: {},
		
		start: function( data ){
			this.start_time = Math.floor( Date.now() / 1000 );
			this.data = data;
			this.set_progress( this.percent );
			this.trigger_next();
		},
		
		trigger_next: function(){
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					action: 'bv-load-bible',
					data: this.data,
				},
				success: bibleverse.ajax_callback,
				dataType: 'json',
			});	
		},
		
		ajax_callback: function( response ){
			if( response.success ){
								
				bibleverse.set_progress( response.data.percent );
				
				if( bibleverse.percent < 100 ){
					bibleverse.el.$feedback.text( response.data.feedback );
					bibleverse.trigger_next();
				}else{
					bibleverse.end();
				}
				
			}else{
				//handle error
				bibleverse.el.$progressbar.css( 'background', '#E92525' );
				bibleverse.el.$feedback.text( response.data.message ).css( 'color', '#E92525' );
				console.log( response );
			}
		},
		
		set_progress: function( percent ){
			this.percent = parseInt( percent, 10 );
					
			//Lets give it a 1% boost
			percent = Math.floor( this.percent * ( 99 / 100 ) ) + 1;
			
			this.el.$progressbar.text( percent+'%' );
			this.el.$progresssr.attr( 'aria-valuenow', percent );
			this.el.$progressbar.text( percent + '% Complete' ).css( 'width', percent + '%' );
		},
		
		end: function(){
			this.el.$progressbar.text( 'Complete' ).css( 'background', '#5cb85c' );
			bibleverse.el.$feedback.hide();
		}
				
	};

	jQuery(document).ready(function($){
		$('.ibv-install-bible').on( 'click', function( e ){
			e.preventDefault();
	
			bibleverse.el.$progresswrap = $( '<div class="ibv-progress"></div>');
			bibleverse.el.$progressbar  = $( '<div class="ibv-progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>');
			bibleverse.el.$progresssr   = $( '<span class="ibv-sr-only">0% Complete</span>' );
			bibleverse.el.$feedback     = $( '<span class="description ibv-feedback">Installing...</span>' );
			
			bibleverse.el.$progresswrap.append( bibleverse.el.$progressbar ).append( bibleverse.el.$progresssr );
			$(this).replaceWith( bibleverse.el.$progresswrap );
			
			bibleverse.el.$feedback.insertAfter( bibleverse.el.$progresswrap );
			
			bibleverse.percent = $(this).data('percent');
			bibleverse.start({
				translation: $(this).data('translation'),
				nonce: $(this).data('nonce')
			});
			
		});
		
		$('.ibv-uninstall-bible').on( 'click', function( e ){
			e.preventDefault();
			
			var $this = $(this);

			if( $(this).data('ibv-lock') ){
				$(this).data('ibv-lock', 1 );
			}
	
			var $description = $(this).parents( '.description' );
			$description.text( 'Uninstalling...' );
			
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					action: 'bv-uninstall-bible',
					data: {
						translation: $(this).data('translation'),
						nonce: $(this).data('nonce')
					}
				},
				success: function( r ){
					var text;
					if( r.success ){
						text = 'Uninstalled';
					}else{
						text = 'Error: ' + r.data.message;
					}
					
					$description.text( text ).fadeIn();
					$this.data('ibv-lock', 0 );
				},
				dataType: 'json',
			});	
		});
	});
	
} )( this );