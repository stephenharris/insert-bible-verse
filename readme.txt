=== Insert Bible Verse ===
Contributors: stephenharris
Donate link: http://stephenharris.info
Tags: bible,verse,scripture,tinymce,esv,net,kjv,web,asv,wbt,ylt
Requires at least: 4.2.0
Tested up to: 4.2.2
Stable tag: 0.1.0
License: GPLv2 or later

Provides a shortcode to insert Bible verses into the body of any page, post or custom post type. 
Provides a tinyMCE button to insert the shortcode with live preview.

== Description ==

**Please note that this plug-in requires WordPress 4.2 or later**

Insert Bible Verse provides the following shortcode

`
[bible_verse translation="esv" book="john" chapter=3 verse=6]
`

to display a Bible verse in any page or post, in any one of 7 Bible translations. The plug-in also adds a 
button to the editor, with provides a UI for entering the shortcode, and a live preview within the editor.

= Provided translations =

 - English Standard Version (ESV)
 - New English Translation (NET)
 - King James Bible (KJV)
 - American Standard Version (ASV)
 - World English Bible (WEB)
 - Webster Bible Translation (WBT)
 - Young Literal Translation (YLT)
 
Where possible, more shall be added as and when requested.

= Plug-in settings =

The plug-in settings can be found near the bottom of **Settings > Writing**.


= A Remark about translations =
Where translations are in the public domain (such as the King James Bible), these translations are included 
within the plug-in, and can be installed in the plug-in settings.

Translations in copyright are provided via third-party API services. Calls to such services are cached to 
minimize page load times. 
 

== Installation ==

= Manual Installation =

1. Upload the entire `insert-bible-verse` directory to the `/wp-content/plugins/` directory.
2. Activate Bible Verse through the 'Plugins' menu in WordPress.
3. Proceed to **Settings > Writing** to configure the plug-in and install translations where appropriate.

== Frequently Asked Questions ==

= Where are the plug-in settings? =

The plug-in settings can be found near the bottom of **Settings > Writing**. Here you can install or uninstall
included translations, and set the default Bible translation for your site.

== Screenshots ==

1. The TinyMCE button opens a modal to select the verse(s) to insert into the page
2. Once inserted, the shortcode displays as a 'live preview' of the inserted verses.
3. An inserted verse on the TwentyThirteen theme
4. The plug-in settings. Public domain Bible translations can be installed locally. Other translations are 
provided via third-party API services.


== Changelog ==

= 0.1.0 =
* First release

== Upgrade Notice ==
