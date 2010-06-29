/*
 * Filename:	jQuery.jGlideMenu.js (formally fastFind)
 * Format:	Javascript Plugin : jQuery Framework
 * Author:      jmcclure@sonicradish.com
 * Copyright:   2006-2010 sonicradish.com
 *
 * License:     You may use this code on your site and alter it as you see fit, all I ask is that you include a reference to my original version and send me any bug fixes that you find.
 * 
 * Revision:	0.69
 * Updated:	2008-03-24 - Version 0.64
 * 		2009-03-03 - Version 0.65
 *		2009-04-01 - Version 0.66 - Added fix for IE7 
 *		2009-04-07 - Version 0.67 - Udated to Work with Latest jQuery Library (1.3.2) and with jQuery UI (1.7.1) Support
 *			                  - Added variable "demoMode" allowing whether clicked links follow through or not
 *					  - Fixed CSS Bug That Prevented Right Arrows from Displaying in Chrome/Safari
 *		2010-03-06 - Version 0.69 - Removed Keyboard Support as it was too broad and breaking other items on the page
 *
 *	Please send feedback and questions to: jmcclure@sonicradish.com
 */

/*
 * To Do Items:
 * 
 *	- Drop Shadow Support 
 *	- Toggle Menu Support (with in/out effects)	
 *		- Basic Support (Fade/Slide/Animate)
 *		- Enchant Library Support
 *	- Launch at Mouse Position Support
 *	- Easing FX Support
 *	- Support for Multiple Menus on Single Page
 *
 */

/*
 * Known Issues
 *
 * 	- When slideRight == false, tile contents aren't rendered until slide completes
 *	- When multiple instances on single page, all events occur in last instance
 *	- DropShadow/Shadow Plugins not currently working
 * 	- Easing transitions not working for horizontal scrolling
 *	- Toggle Method Needs Further Development
 *	- Keyboard Support Needs Work (Space Key Triggers without mouse Over)
 *
 * To Do
 *
 *	- Better Keyboard Support
 * 	- shadowBox (inline effect)
 *	- MouseHold Instead of/or in addition to Hover
 *
 */

/*
 * Testing & Support	Version 0.6.7
 *
 * 	+ Firefox
 *		+ 2.x (XP)		Okay
 *		+ 2.x (OSX)		Okay
 *		+ 3.x			Okay
 *	
 *	+ Internet Explorer
 *		+ 6.x			Okay
 *		+ 7.x			Okay
 *
 *	+ Opera
 *		+ 9.26			Okay
 *		+ 9.64			Okay
 *	
 *	+ Safari
 *		+ 3.x (XP)		Okay
 *		+ 3.x (OSX)		Okay
 *		+ 4.x (XP)		Okay
 *
 *	+ Google Chrome
 *		+ 1.0.x			Okay
 */

jQuery.jGlideMenu = {

	// <-- Global Declarations

	// Set By Configuration
	useDropShadow 		:	new Boolean(),
	useDragDrop 		:	new Boolean(),
	defaultScrollSpeed	:	new Number(0),
	defaultScrollBackSpeed	:	new Number(0),
	slideRight		:	new Boolean(),
	useSmoothScrolling	:	new Boolean(),
	easeFx			:	new String(''),
	closeLinkMarkUp		:	new String(''),
	menuShowFx		:	new String(''),
	menuHideFx		:	new String(''),
	width			:	new Number(0),
	height			:	new Number(0),
	tileWidth		:	new Number(0),
	tileInset		:	new Number(0),
	itemsToDisplay		:	new Number(8),
	useTileURL		:	new Boolean(),
	tileSource		:	new String(''),
	URLParams		:	new Object(),
	loadImage		:	new String(''),
	loadImageStyle		:	new Object(),
	initialTile		:	new String(''),
	alertOnError		:	new Boolean(),
	captureLinks		:	new Boolean(),
	imagePath		:	new String(),

	// Set By jGlideMenu Script
	tileCount		:	new Number(0),
	animation		:	new Boolean(),
	helperImage		:	new Boolean(),
	currentElement		:	new Object(),
	currentElementID	:	new String(''),
	hasDragDropSupport	:	new Boolean(),
	hasShadowSupport	:	new Boolean(),
	displayToggle		:	new Boolean(),
	tileScrollPosition	:	new Array(),
	smoothScrollTimer	:	new Array(),
	mouseHover		:	new Boolean(),
	demoMode		: 	new Boolean(),

	// --> Global Declarations

	// Create Plugin Instance
	initialize : function(o)
	{
		// Return jQuery
                return this.each(function(){ 

			// Init Variables	
			jQuery.jGlideMenu.animation 		= false;
			jQuery.jGlideMenu.helperImage 		= false;
			jQuery.jGlideMenu.hasDragDropSupport 	= false;
			jQuery.jGlideMenu.hasShadowSupport 	= false;
			jQuery.jGlideMenu.tileCount 		= 0;	
			jQuery.jGlideMenu.displayToggle		= false;
			jQuery.jGlideMenu.mouseHover		= false;
			jQuery.jGlideMenu.demoMode		= false;
	
			// Store Current Element	
			jQuery.jGlideMenu.currentElement = jQuery(this);	
			if(this.id) jQuery.jGlideMenu.currentElementID = this.id;
			
			// Default Values
			var s = {
					itemsToDisplay		:	16,
					tileInset		:	7,
					tileWidth		:	627,
					height			:	288,
					width			:	813,
					useDropShadow		:	false,
					slideRight		:	true,
					useDragDrop		:	true,
					useSmoothScrolling	:	true,
					useTileURL		:	false,
					defaultScrollSpeed	:	750,
					defaultScrollBackSpeed	:	800,
					tileSource		:	'myTiles',
					URLParams		:	{},
					closeLinkMarkUp		:	'Close',
					menuShowFx		:	'fadeIn',
					menuHideFx		:	'fadeOut',	
					easeFx			:	'linear',
					loadImage		:	'img/ajax.gif',
					initialTile		:	'tile_001',
					alertOnError		:	false,
					captureLinks		:	true,
					loadImageStyle	 	:	{ 'position' : 'absolute', 'bottom' : '10px', 'left' : '10px' , 'z-index' : '99' },
					imagePath		:	'/metarep/img/',
					demoMode		:	false
				};



			// Merge Submitted Settings
			if(o) jQuery.extend(s,o);

	
			
			// Check Library Support for FX
			jQuery.jGlideMenu.checkFeatures();

			// Ensure Values (Basic)
			if(s.closeLinkMarkUp.length<1) s.closeLinkMarkUp = 'x Close';	
			if(parseInt(s.itemsToDisplay) < 1) s.itemsToDisplay = 1;
			if(s.initialTile.length < 0) jQuery.jGlideMenu.errorTrap('Invalid Configuration');
	
			// Set Global Values 
			for(i in s) jQuery.jGlideMenu[i] = s[i];

			// <-- Remove Any Place Holder Content From Element and Hide Tiles
			// Hide Tiles In View (if DOM Mode)
                        if(jQuery.jGlideMenu.useTileURL == false && jQuery.jGlideMenu.tileSource.length > 0)
			{
                                jQuery(jQuery.jGlideMenu.tileSource).css('display','none');
				// If Tiles are Inside of Element, Remove Everything Else
				/*
					// Old Method Used with jQuery 1.2.x
					var x = jQuery(jQuery.jGlideMenu.currentElement).children();
					jQuery(x+':not('+jQuery.jGlideMenu.tileSource+')').remove();
				*/
				jQuery(jQuery.jGlideMenu.currentElement).children().not(jQuery.jGlideMenu.tileSource).remove();
			}
			else jQuery(jQuery.jGlideMenu.currentElement).html('');
			// --> Remove Any Place Holder Content From Element and Hide Tiles

			// <-- Create Menu Structure
			jQuery(jQuery.jGlideMenu.currentElement).append('<div class="jGM_header"><a href="#">'+jQuery.jGlideMenu.closeLinkMarkUp+'</a></div>')
								.append('<div class="jGM_wrapper" id="jGM_wrapper_'+this.id+'"></div>');
			// --> Create Menu Structure
		
			// <-- Create Animation/Load Image
			var img = document.createElement('img');
			img.src = jQuery.jGlideMenu.loadImage;
			img.style.display = 'none';
			img.id  = 'jGM_helper'+jQuery.jGlideMenu.currentElementID;
			jQuery(jQuery.jGlideMenu.currentElement).append(img);
			jQuery('img#'+img.id).css(jQuery.jGlideMenu.loadImageStyle);
			jQuery.jGlideMenu.helperImage = true;
			// --> Create Animation/Load Image

			// Add Drag Drop Support
			if(jQuery.jGlideMenu.hasDragDropSupport == true && jQuery.jGlideMenu.useDragDrop == true)
			{
				if(jQuery.isFunction(jQuery('body').Draggable))
					jQuery(this).Draggable({ handle : '.jGM_header' });
				else
					jQuery(this).draggable({ handle : '.jGM_header' });
			}

			// Add Drop Shaddow Support
			if(jQuery.jGlideMenu.hasShadowSupport == true && jQuery.jGlideMenu.useDropShadow == true)
			{
				if(jQuery.isFunction(jQuery('body').dropShadow))
					jQuery(this).dropShadow();
				else
					jQuery(this).shadow({color: '#cccccc'});
			}

			// Triggle Close Button
//			jQuery(jQuery.jGlideMenu.currentElement).find('div.jGM_header a').bind('click',function(){ 
//				if(jQuery.jGlideMenu.displayToggle==true) return false;
//				jQuery.jGlideMenu.toggleDisplay(true);
//				return false;
//			});

			// Check for Mouse Over Menu
			jQuery(this).hover(function(){ jQuery.jGlideMenu.mouseHover = true; },function(){ jQuery.jGlideMenu.mouseHover = false; });

			// Bind Keyboard Events (Top Level)
			jQuery(document).keydown(function(e) 
			{
				// Disabled in this version:
					return true;

				var key = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;

				// Toggle Display when Space is Pressed
				if(key == 32 && jQuery.jGlideMenu.mouseHover == false) { jQuery.jGlideMenu.toggleDisplay(); return; }

				if(jQuery.jGlideMenu.mouseHover == false) return false;
				switch(key)
				{
					// Left
					case 37: 
				  		break;
					// Up
					case 38: 
				  		break;
					// Right
					case 39: 
				  		break;
					// Down
					case 40: 
				  		break;
					// return
					case 13: 
				  		break;
					// space
					case 32:
						jQuery.jGlideMenu.toggleDisplay(); return;
						break;
				}
		  	});
			// Load Initial Tile
			jQuery.jGlideMenu.loadTile(jQuery.jGlideMenu.initialTile,jQuery.jGlideMenu.URLParams);

			var str1="image path:";
			//alert(str1.concat(jQuery.jGlideMenu.imagePath));		
		});
	},

	// Toggle Display of Menu
	toggleDisplay : function(r) 
	{
		jQuery.jGlideMenu.displayToggle = true;
		jQuery.jGlideMenu.mouseHover == false;
                if(jQuery(jQuery.jGlideMenu.currentElement).css('display')=='block')
                        var toggle_value = 0;
                else
                        var toggle_value = 1;
                jQuery(jQuery.jGlideMenu.currentElement).animate(
                        {
                                opacity: toggle_value
                        }, 'slow', function ()
                        {
                                // Check for Reset Flag
                                if(r==true)
				{
                                        jQuery.jGlideMenu.scrollToTile(0,jQuery.jGlideMenu.defaultScrollBackSpeed);
					//jQuery.jGlideMenu.tileScrollPosition = [];
					jQuery.jGlideMenu.tileScrollPosition[0] = 0;
				}
				if(toggle_value > 0)
					jQuery(this).css('display','block');
				else
					jQuery(this).css('display','none');
				jQuery.jGlideMenu.displayToggle = false;
                        }
                );
	},

	// Check Feature Availability
	checkFeatures : function() {
		// Check Drap Drop Support (jQuery Interface)
                jQuery.jGlideMenu.hasDragDropSupport = jQuery.isFunction(jQuery('body').Draggable);
		// Check Drag Drop Support (jQuery UI)
		if(jQuery.jGlideMenu.hasDragDropSupport == false)
	                jQuery.jGlideMenu.hasDragDropSupport = jQuery.isFunction(jQuery('body').draggable);

		// <-- Not Supported Currently
			// Check Shadow Support (DropShadow Plugin)
			jQuery.jGlideMenu.hasShadowSupport   = jQuery.isFunction(jQuery('body').dropShadow);
			// Check Shadow Support (jQuery UI)
			if(jQuery.jGlideMenu.hasShadowSupport == false)
				jQuery.jGlideMenu.hasShadowSupport   = jQuery.isFunction(jQuery('body').shadow);
		// --> Not Supported Currently
		return;
	},

	parseURL : function(u) {
		// MSIE 6 (maybe 7) Returns #tile_001 as http://...#tile_001
	
		// ## $.browser is Depreciated !!! ##
		if(!jQuery.browser.msie)
		{
                	return u;
		}
		if(u.indexOf('#tile_')<0) 
		{
			// Regular Link
			return u;
		}
		// Navigation Link
		var bits = u.split('#');
		return '#'+bits[(bits.length-1)];
	},

	// Return Number of Active Tiles
	countTiles : function() { jQuery.jGlideMenu.tileCount = parseInt(jQuery('div.jGM_tile').size()); },

	// Create and Load Tile
	loadTile : function(u,p)
	{
		// Create New Tile Wrapper
		var ptr = document.createElement('div');
		// New Tile Position
		var ctr = jQuery.jGlideMenu.tileCount + 1;
		ptr.id  = 'jGM_tile_'+jQuery.jGlideMenu.currentElementID+'_'+ctr;
		// Position of Tile
		if(jQuery.jGlideMenu.slideRight == true)
			var off = jQuery.jGlideMenu.tileWidth * jQuery.jGlideMenu.tileCount + jQuery.jGlideMenu.tileInset;
		else
			var off = jQuery.jGlideMenu.tileWidth * jQuery.jGlideMenu.tileCount * -1 + jQuery.jGlideMenu.tileInset;
		// Add to DOM
		jQuery('#jGM_wrapper_'+jQuery.jGlideMenu.currentElementID).append(ptr);
		// Apply Class & Style to Tile
		//alert(jQuery.jGlideMenu.width);
		jQuery('#'+ptr.id).addClass('jGM_tile').css({
			top		:	0,
			left		:	off+'px',
			height		:	jQuery.jGlideMenu.height+'px',
			width		:	jQuery.jGlideMenu.width+'px',
			position	:	'absolute',
			overflow	:	'hidden',
			margin		:	0,
			padding		:	0,
			border		:	0,
			display		:	'block'	
		});
		
//		jQuery('#'+ptr.id).addClass('jGM_content').css({
//			width		:	jQuery.jGlideMenu.width+'px !important',
//		});		
		

		// Default Tile Content
		var tmpl  = '<div style="height:100%;margin:0;border:0;width:100%;padding:0;text-align:center;">'
                                    +'<h3>Missing Tile</h3><p>Unable to locate the requested Tile</p></div>';

		// Load Content Into Tile
		if(jQuery.jGlideMenu.useTileURL == false)
		{
			// DOM
			if(jQuery('ul#'+u).size()<1)
			{
				jQuery.jGlideMenu.errorTrap('Invalid Tile Request');
				return false;
			}
			var title = jQuery('ul#'+u).attr('title');
                	var desc  = jQuery('ul#'+u).attr('alt');
                	var items = jQuery('ul#'+u+' li').size();
                	var links = [];
                	jQuery('ul#'+u+' li').each(function(){
				if(jQuery('a',this).size()>0)
					links[links.length] = [jQuery('a',this).attr('href'),jQuery('a',this).text(),1];
				else
                        		links[links.length] = [jQuery(this).attr('rel'),jQuery(this).text(),0];
                	});
			var tmpl  = jQuery.jGlideMenu.buildTile(title,desc,links);
		}
		else
		{
			// AJAX
			if(jQuery.jGlideMenu.tileSource.length < 1)
			{
				jQuery.jGlideMenu.errorTrap('Invalid AJAX Request');
				return false;
			}

			// Bind AJAX Events
			var mon = 'img#jGM_helper'+jQuery.jGlideMenu.currentElementID;
                        jQuery(mon).ajaxStart(function() { jQuery(this).animate({opacity:'show'},'fast'); })
                                   .ajaxStop( function() { jQuery(this).animate({opacity:'hide'},'slow'); });
			p.tile = u;
			jQuery.ajax({
				type: "POST",
				url		: jQuery.jGlideMenu.tileSource,
				data		: p,
				async		: false,
				success 	: function(xhtml){ 
							// Make it Usable --> "var dom = jQuery(xhtml)" is crashing FF2
							jQuery('body').append('<div id="jGM_temp_'+jQuery.jGlideMenu.currentElementID+p.tile+'" style="display:none;">'+xhtml+'</div>');
							if(jQuery('#jGM_temp_'+jQuery.jGlideMenu.currentElementID+p.tile+' ul#'+u).size()<1)
                        				{
                                				jQuery.jGlideMenu.errorTrap('AJAX: Invalid Tile Request');
                                				return false;
                        				}
                        				var title = jQuery('#jGM_temp_'+jQuery.jGlideMenu.currentElementID+p.tile+' ul#'+u).attr('title');
                        				var desc  = jQuery('#jGM_temp_'+jQuery.jGlideMenu.currentElementID+p.tile+' ul#'+u).attr('alt');
                        				var items = jQuery('#jGM_temp_'+jQuery.jGlideMenu.currentElementID+p.tile+' ul#'+u+' li').size();
                        				var links = [];
                        				jQuery('#jGM_temp_'+jQuery.jGlideMenu.currentElementID+p.tile+' ul#'+u+' li').each(function(){
								if(jQuery('a',this).size()>0)
				                                        links[links.length] = [jQuery('a',this).attr('href'),jQuery('a',this).text(),1];
                                				else
                                        				links[links.length] = [jQuery(this).attr('rel'),jQuery(this).text(),0];
                        				});
							// Remove Temporary Tile
							jQuery('#jGM_temp_'+jQuery.jGlideMenu.currentElementID+p.tile).remove();
                        				tmpl  = jQuery.jGlideMenu.buildTile(title,desc,links);		
							// Remove AJAX Event Triggers					
							jQuery(mon).ajaxStart(function(){}).ajaxStop(function(){});
						 },
				error		: function(rslt){ 
							jQuery.jGlideMenu.errorTrap('Invalid AJAX Tile Request'); 
							// Remove AJAX Event Triggers
                                                        jQuery(mon).ajaxStart(function(){}).ajaxStop(function(){});
						}
			}); 
		}

		// Add to DOM
                jQuery('#'+ptr.id).html(tmpl);
	
		// Catch Click Event for List Items	
		jQuery('#'+ptr.id+' div.jGM_content a').bind('click',function() {
				var target='';
				target = jQuery.jGlideMenu.parseURL(jQuery(this).attr('href'));

				if(target.length<1) { return false; }

				if(target.substr(0,1)=='#')
				{
					if(jQuery.jGlideMenu.animation == true) return false;
					var dest = target.substr(1,target.length-1);
					//alert('Pager Scroll Request: '+target);
					jQuery.jGlideMenu.loadTile(dest,jQuery.jGlideMenu.URLParams);
					return false;
				}
				else
				{
					if(jQuery.jGlideMenu.demoMode)
					{
						alert('Navigation Requestion: '+target);
					}
					else
					{
						window.location.href = target;
					}

					// Prevent Default Action As Needed
					if(jQuery.jGlideMenu.captureLinks == true) return false;
				}
				return true;
	   	});

		// Update Tile Count
                jQuery.jGlideMenu.countTiles();

		// Add Back/Reset Buttons (as Needed)
		if(jQuery.jGlideMenu.tileCount > 1)
		{
			// Insert Back Button
			jQuery('#'+ptr.id).append('<div class="jGM_back"><a href="#">&laquo; Back</a></div>');
			jQuery('#'+ptr.id+' div.jGM_back').bind('click',function() {
				if(jQuery.jGlideMenu.animation == true) return false;
				jQuery.jGlideMenu.scrollToTile((ctr-1),jQuery.jGlideMenu.defaultScrollBackSpeed);
				return false;
			});
			
			if(jQuery.jGlideMenu.tileCount > 2)
			{
				// Insert Reset Button
				jQuery('#'+ptr.id).append('<div class="jGM_reset"><a href="#">&laquo; Home</a></div>');
				jQuery('#'+ptr.id+' div.jGM_reset').bind('click',function() {
					if(jQuery.jGlideMenu.animation == true) return false;
                                	jQuery.jGlideMenu.scrollToTile(1,jQuery.jGlideMenu.defaultScrollBackSpeed);
                                	return false;
				});
			}
		}

		// Set Tile Scroll Position
		jQuery.jGlideMenu.tileScrollPosition[ctr] = 0;

		// Set Pager (Init)
		jQuery.jGlideMenu.drawPagers(ptr.id,jQuery('#'+ptr.id+' .jGM_content a').size());

		// Catch Pager Scroll
		if(jQuery.jGlideMenu.useSmoothScrolling == false)
		{
			jQuery('#'+ptr.id+' .jGM_pager a').click(function(){
				var dir = 1;
				if(jQuery(this).attr('rel') == 'Up') dir = 0;
				jQuery.jGlideMenu.scrollItems(dir);
			});
		}
		else
		{
			jQuery('#'+ptr.id+' .jGM_pager a').hover(function(){
				var dir = 1;
                                if(jQuery(this).attr('rel') == 'Up') 
					dir = 0;
				jQuery.jGlideMenu.smoothScrollTimer[jQuery.jGlideMenu.tileCount] 
					= window.setInterval('jQuery.jGlideMenu.scrollItems('+dir+')',250);
			},function(){
				window.clearInterval(jQuery.jGlideMenu.smoothScrollTimer[jQuery.jGlideMenu.tileCount]);
			});
		}

		// Scroll
                jQuery.jGlideMenu.scrollToTile(ctr,jQuery.jGlideMenu.defaultScrollSpeed);

	},

	// Scroll Items
	scrollItems : function(d)
	{
		var x = '#jGM_tile_'+jQuery.jGlideMenu.currentElementID+'_'+jQuery.jGlideMenu.tileCount;
		var s = jQuery(x+' .jGM_content a');
		var c = jQuery.jGlideMenu.tileScrollPosition[jQuery.jGlideMenu.tileCount];
		// Enforce Bounds
		if(c <= 0 && d == 0) return;
		if(c+jQuery.jGlideMenu.itemsToDisplay >= jQuery(s).size() && d == 1) return;
		// Handle Scroll
		if(d == 0)
			jQuery.jGlideMenu.tileScrollPosition[jQuery.jGlideMenu.tileCount]--;
		else
			jQuery.jGlideMenu.tileScrollPosition[jQuery.jGlideMenu.tileCount]++;
		jQuery(s).show();
		jQuery(x+' .jGM_content').children('a:lt('+jQuery.jGlideMenu.tileScrollPosition[jQuery.jGlideMenu.tileCount]+')').hide();
		jQuery.jGlideMenu.drawPagers(x.substr(1,x.length),jQuery(s).size());
	},

	// Draw Pager Controls (Toggle Visibility)
	drawPagers : function(p,c)
	{
		jQuery('#'+p+' .jGM_pager').find('a').each(function(){
			if(jQuery(this).attr('rel') == 'Up')
			{
				if(jQuery.jGlideMenu.tileScrollPosition[jQuery.jGlideMenu.tileCount]>0)
					jQuery(this).css('display','block');
				else
					jQuery(this).css('display','none');
			}
			else
			{
				if(jQuery.jGlideMenu.tileScrollPosition[jQuery.jGlideMenu.tileCount]+jQuery.jGlideMenu.itemsToDisplay <
						jQuery('#'+p+' .jGM_content a').size())
					jQuery(this).css('display','block');
				else
					jQuery(this).css('display','none');
			}
		});
	},

	// Remove Tiles
	cleanTiles : function(n) 
	{
		var start = n + 1;
                var stop  = jQuery.jGlideMenu.tileCount;

		if(n>=stop) return false;

		for(var i=start;i<=stop;i++)
		{
			jQuery('#jGM_tile_'+jQuery.jGlideMenu.currentElementID+'_'+i).remove();
			jQuery.jGlideMenu.tileScrollPosition[i] = 0;
		}

		jQuery.jGlideMenu.countTiles();

		return;
	},

	// Handle Horizontal Scroll
	scrollToTile : function(n,s)
	{

		// Get Tile Count
		jQuery.jGlideMenu.countTiles();
		var t = jQuery.jGlideMenu.tileCount;
	
		// Enforce Bounds	
		if(n>t) n = t;
		if(n<1) n = 1;

		// Set Speed	
		if(!s)  s = jQuery.jGlideMenu.defaultScrollSpeed;

		var b = (jQuery.jGlideMenu.tileWidth * n) - jQuery.jGlideMenu.tileWidth;
		var a = (jQuery.jGlideMenu.slideRight == true)?b*-1:b;
		// Animate
		jQuery.jGlideMenu.animation = true;
		jQuery('div#jGM_wrapper_'+jQuery.jGlideMenu.currentElementID).animate({ 'left' : a }, s, jQuery.jGlideMenu.easeFx, function(){
				// Remove Extra Tiles
				if(n<t)
					jQuery.jGlideMenu.cleanTiles(n);
				if(a != 0) a += 'px !important';
				jQuery(this).css({ 'left' : a });

				jQuery.jGlideMenu.animation = false;
		});
	},

	// Return Template
	buildTile : function(t,d,l)
	{
		if(jQuery.jGlideMenu.imagePath.length>1 && jQuery.jGlideMenu.imagePath.substr(-1,1)!='/')
			jQuery.jGlideMenu.imagePath += '/';

		var template = new String('');
		// Header Layout
		template  = '<div class="jGM_cats"><h1>'+t+'</h1><p class="jGM_desc">'+d+'</p></div>';
		// Scroll Up
		//alert('<img src="'+jQuery.jGlideMenu.imagePath+'arrow_up.gif"');
		template += '<div class="jGM_pager"><a href="#" rel="Up" title="Scroll Up" style="display:none"><img src="'
				+jQuery.jGlideMenu.imagePath+'arrow_up.gif" alt="Scroll Up"/></a></div>';
		// Items
		template += '<div class="jGM_content">';
		for(var i=0;i<l.length;i++)
		{
			var hash = (l[i][2]==1)?'':'#';
			var type = (l[i][2]==1)?'':' class="jGM_more"';
			template += '<a href="'+hash+l[i][0]+'"'+type+'>'+l[i][1]+'</a>';
			//<a href="fetch://{'current':5,'previous':1,'apiKey':'bd51b0648d268122996b9e68cfd86175','client':'ActiveSpotLight'}"  class="jGM_more">More Options..</a>
		}
		template += '</div>';
		// Scroll Down
		template += '<div class="jGM_pager"><a href="#" rel="Down" title="Scroll Down" style="display:none"><img src="'
				+jQuery.jGlideMenu.imagePath+'arrow_dn.gif" alt="Scroll Up"/></a></div>';
		return template;	
	},

	// Error Function
	errorTrap : function(m)
	{
		if(jQuery.jGlideMenu.alertOnError == true)
			alert(m);
		return;
	}
}

// Extend Global jQuery Functions
jQuery.fn.jGlideMenu		= jQuery.jGlideMenu.initialize;
jQuery.fn.jGlideMenuToggle	= jQuery.jGlideMenu.toggleDisplay;
jQuery.fn.reverse		= function() { return this.pushStack(this.get().reverse(), arguments); };