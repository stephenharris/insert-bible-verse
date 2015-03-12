/*globals window, document, $, jQuery, _, Backbone */
(function ($, _, Backbone) {
	"use strict";
	var media = wp.media,

	ThingDetailsController = media.controller.State.extend({
		defaults: {
			id: 'bible-verse',
			title: 'Insert Bible verse',
			toolbar: 'bible-verse',
			content: 'bible-verse',
			menu: 'bible-verse',
			router: false,
			priority: 60
		},

		initialize: function( options ) {
			this.thing = options.thing;
			media.controller.State.prototype.initialize.apply( this, arguments );
		}
	}),

	ThingDetailsView = media.view.Settings.AttachmentDisplay.extend({
		className: 'bible-verse',
		template:  media.template( 'bible-verse' ),
		prepare: function() {
			var books = [
				'genesis','exodus','leviticus','numbers','deuteronomy','joshua','judges','ruth','1 samuel',
				'2 samuel','1 kings','2 kings','1 chronicles','2 chronicles','ezra','nehemiah','esther',
				'job','psalms','proverbs','ecclesiastes','song of solomon','isaiah','jeremiah',
				'lamentations','ezekiel','daniel','hosea','joel','amos','obadiah','jonah','micah','nahum',
				'habakkuk','zephaniah','haggai','zechariah','malachi','matthew','mark','luke','john','acts',
				'romans','1 corinthians','2 corinthians','galatians','ephesians','philippians','colossians',
				'1 thessalonians','2 thessalonians','1 timothy','2 timothy','titus','philemon','hebrews',
				'james','1 peter','2 peter','1 john','2 john','3 john','jude','revelation'];
			return _.defaults( {
				model: this.model.toJSON(),
				books: books,
				max_chapters: _.size( bibleverse.scripture[this.model.get('book')] )
			}, this.options );
		},
		
		events: {
			'change': 'updateChapters',
		},
		
		initialize: function(){
			this.model.on( 'change:book', this.renderChapters, this );
		},
		
		renderChapters: function(){
			//A bit of a hacky, re-render modal settings and put focus back to the book dropdown
			this.render();
			$('.ibv-book-field', this.$el ).focus();
			
		},
		
		updateChapters: function(){
			//this.max_chapters = _.size( bibleverse.scripture[this.model.get('book')] )
			this.model.set({
				'book':    $('.ibv-book-field', this.$el ).val(),
				'chapter': $('.ibv-chapter-field', this.$el ).val(),
				'verse':   $('.ibv-verse-field', this.$el ).val(),
			});
		}
	}),

	ThingDetailsFrame = media.view.MediaFrame.Select.extend({
		defaults: {
			id:      'bible_verse',
			url:     '',
			type:    'link',
			title:   'Thing!',
			priority: 120
		},

		initialize: function( options ) {
			this.thing = new Backbone.Model( options.metadata );
			this.options.selection = new media.model.Selection( this.thing.attachment, { multiple: false } );
			media.view.MediaFrame.Select.prototype.initialize.apply( this, arguments );
		},
		

		bindHandlers: function() {
			media.view.MediaFrame.Select.prototype.bindHandlers.apply( this, arguments );

			this.on( 'menu:create:bible-verse', this.createMenu, this );
			this.on( 'content:render:bible-verse', this.contentDetailsRender, this );
			this.on( 'menu:render:bible-verse', this.menuRender, this );
			this.on( 'toolbar:render:bible-verse', this.toolbarRender, this );
		},

		contentDetailsRender: function() {
			var view = new ThingDetailsView({
				controller: this,
				model: this.state().thing,
				attachment: this.state().thing.attachment
			}).render();

			this.content.set( view );
			
		},

		menuRender: function( view ) {
			var lastState = this.lastState(),
				previous = lastState && lastState.id,
				frame = this;
		},

		toolbarRender: function() {
			this.toolbar.set( new media.view.Toolbar({
				controller: this,
				items: {
					button: {
						style:    'primary',
						text:     'Insert verse',
						priority: 80,
						click:    function() {
							var controller = this.controller;
							controller.close();
							controller.state().trigger( 'update', controller.thing.toJSON() );
							controller.setState( controller.options.state );
							controller.reset();
						}
					}
				}
			}) );
		},

		createStates: function() {
			this.states.add([
				new ThingDetailsController( {
					thing: this.thing
				} ),
			]);
		}
	}),

	thing = {
		coerce : media.coerce,

		defaults : {
			book : 'genesis',
			chapter : 1,
		},

		edit : function ( data ) {
			var frame, shortcode = wp.shortcode.next( 'bible_verse', data ).shortcode;
			frame = new ThingDetailsFrame({
				frame: 'bible_verse',
				state: 'bible-verse',
				metadata: _.defaults( shortcode.attrs.named, thing.defaults )
			});

			return frame;
		},

		shortcode : function( model ) {
			var self = this, content;

			/*_.each( thing.defaults, function( value, key ) {
				model[ key ] = self.coerce( model, key );

				if ( value === model[ key ] ) {
					delete model[ key ];
				}
			});*/

			content = model.content;
			delete model.content;
				console.log( model );
			var wpshortcode = new wp.shortcode({
				tag: 'bible_verse',
				attrs: model,
				content: content,
			});
			
			return wpshortcode;
		}
	},

	thingMce = {

		View: {
			
			shortcodeHTML: false,
			
			className: 'editor-bible-verse',
			
			//fetch: function() {},
			initialize: function( options ) {
				this.shortcode = options.shortcode;
			},
			
			setHtml: function( body ) {
				this.shortcodeHTML = body;
				var stylesheet = '<link rel="stylesheet" id="bible-verse-frontend-css" href="' + bibleverse.stylesheet + '" type="text/css" media="all">';
				this.shortcodeHTML = this.shortcodeHTML + stylesheet;
				this.render( true );
				return;
			},
				
			getHtml: function() {
				var data;

				if ( false === this.shortcodeHTML ) {
					data = {
						action: 'bibleverse_do_shortcode',
						post_id: $('#post_ID').val(),
						shortcode: this.shortcode.string(),
						nonce: bibleverse.nonce
					};
					$.post( ajaxurl, data, $.proxy( this.setHtml, this ) );

				}
				
				return this.shortcodeHTML;
	
			},
			
			loadingPlaceholder: function() {
				return '' +
					'<div class="loading-placeholder">' +
						'<div class="dashicons dashicons-bible"></div>' +
						'<div class="wpview-loading"><ins></ins></div>' +
					'</div>';
			},

		},

		edit: function( node ) {
			var self = this, frame, data;

			data = window.decodeURIComponent( $( node ).attr('data-wpview-text') );
			frame = thing.edit( data );
			frame.state('bible-verse').on( 'update', function( selection ) {
				self.shortcodeHTML = false;
				var shortcode = thing.shortcode( selection ).string();
				$( node ).attr( 'data-wpview-text', window.encodeURIComponent( shortcode ) );
				wp.mce.views.refreshView( self, shortcode, true );
				
				frame.detach();
			});
			frame.open();
			frame.$el.parents('.media-modal').addClass('bible-verse-shortcode-modal');
		}
	};
	
	wp.mce.views.register(
		'bible_verse',
		$.extend( true, {}, thingMce )
	);
		
	tinymce.create('tinymce.plugins.insert_verse', {
		init : function(ed, url) {
				// Register commands
				ed.addCommand('insert_verse', function() {
					var frame;
					frame = thing.edit( "[bible_verse book='genesis' chapter=1]" );
					frame.state('bible-verse').on( 'update', function( selection ) {
						var shortcode = thing.shortcode( selection ).string();
						tinymce.execCommand('mceInsertContent', false, shortcode);
						wp.mce.views.refreshView( thingMce, shortcode );
						frame.detach();
					});
					frame.open();
					frame.$el.parents('.media-modal').addClass('bible-verse-shortcode-modal');
				});
			 
			// Register buttons
			ed.addButton('insert_verse', {
				title : 'Insert bible verse', 
				cmd : 'insert_verse', 
				icon: 'bible', 
			});
		}
	});
	 
	// Register plugin
	tinymce.PluginManager.add('insert_verse', tinymce.plugins.insert_verse);
	
}(jQuery, _, Backbone));