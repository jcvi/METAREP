<?php
/* SVN FILE: $Id: default.ctp 7945 2008-12-19 02:16:01Z gwoo $ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @version       $Revision: 7945 $
 * @modifiedby    $LastChangedBy: gwoo $
 * @lastmodified  $Date: 2008-12-18 18:16:01 -0800 (Thu, 18 Dec 2008) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<?php echo $html->charset(); ?>
<title>
	<?php __('JCVI Metagenomics Reports - '); ?>
	<?php echo $title_for_layout; ?>
</title>

<?php
	echo $html->css('jquery-ui-1.7.2.custom.css');
	echo $html->css('cake.generic');		
	echo $scripts_for_layout;			
	echo $javascript->link(array('prototype'));
	echo $javascript->link(array('scriptaculous'));
	echo $javascript->link(array('jquery/js/jquery-1.3.2.min.js'));		
	echo $javascript->link(array('jquery/js/jquery-ui-1.7.2.custom.min.js'));
?>
	
<script type="text/javascript">
 jQuery.noConflict();
	
	jQuery(function(){			

		// Dialog			
		jQuery('#dialog').dialog({
			autoOpen: false,
			width: 850,
			modal: true,
			buttons: {
				"Ok": function() { 
					jQuery(this).dialog("close"); 
				},
			}
		});
		
		// Dialog Link
		jQuery('#dialog_link').click(function(){
			jQuery('#dialog').dialog('open');
			return false;
		});

		// Datepicker
		jQuery('#datepicker').datepicker({
			inline: true
		});
		
		// Slider
		jQuery('#slider').slider({
			range: true,
			values: [17, 67]
		});
		
		// Progressbar
		jQuery("#progressbar").progressbar({
			value: 20 
		});
		
		//hover states on the static widgets
		jQuery('#dialog_link, ul#icons li').hover(
			function() { jQuery(this).addClass('ui-state-hover'); }, 
			function() { jQuery(this).removeClass('ui-state-hover'); }
		);
		
	});
</script>


 <script type="text/javascript">
	  var is_production = true;
	  var dev_test = /(-dev)|(-test)/;
	  var hostname = location.hostname;
	
	  if(hostname.search(dev_test) != -1) {
	    is_production = false;
	  } // end if(hostname.search(dev_test) != -1)
	
	  if(is_production) {
	    var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	    document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	  } // end if(is_production)
	</script>
	<script type="text/javascript">
	  if(is_production) {
	    try {
	      var pageTracker = _gat._getTracker("UA-9809410-3");
	      pageTracker._setDomainName(".jcvi.org");
	      pageTracker._trackPageview();
	    } catch(err) {}
	  } // end if(is_production)
</script>       	
      
<style type="text/css">
	/*demo page css*/	
	.demoHeaders { margin-top: 2em; }
	#dialog_link {padding: .4em 1em .4em 20px;text-decoration: none;position: relative;}
	#dialog_link span.ui-icon {margin: 0 5px 0 0;position: absolute;left: .2em;top: 50%;margin-top: -8px;}
	ul#icons {margin: 0; padding: 0;}
	ul#icons li {margin: 2px; position: relative; padding: 4px 0; cursor: pointer; float: left;  list-style: none;}
	ul#icons span.ui-icon {float: left; margin: 0 4px;}
</style>
</head>
<body>
	<div id="container">
		<div id="header">
			<h1><?php echo $html->link(__('JCVI Metagenomics Reports', true), '/projects'); ?></h1>			
		</div>		
		<? 
		if (Authsome::get()):?>
			<?php 
				$currentUser 	= Authsome::get();
				$currentUserId 	= $currentUser['User']['id'];	    	        	
	       		$userGroup  	= $currentUser['UserGroup']['name'];
	       	?>	       					
		<ul id="menu">			
			<li><?php echo $html->link(__('Quick Navigation', true), array('controller'=> 'menus', 'action'=>'quick')); ?></li>
			
			<? if (	$userGroup === 'Admin'):?>
				<li><?php echo $html->link(__('New Project', true), array('controller'=> 'projects', 'action'=>'add')); ?></li>
			<?endif;?>			
			<li><?php echo $html->link(__('List Projects', true), array('controller'=> 'projects', 'action'=>'index')); ?> </li>			
			<? if (	$userGroup === 'Admin' || $userGroup === 'JCVI'):?>
			<li><?php echo $html->link(__('List Populations', true), array('controller'=> 'populations', 'action'=>'index')); ?> </li>
				<li><?php echo $html->link(__('Pipeline Log', true), array('controller'=> 'logs', 'action'=>'index')); ?> </li>
			<?endif;?>		
			<li><?php echo $html->link(__('Dashboard', true), array('controller'=> 'dashboard')); ?></li>
			<li><?php echo $html->link(__('Log Out', true), array('controller'=> 'users', 'action'=>'logout')); ?> </li>
		</ul>	
		<?endif;?>		
		
		<div id="content">	
			<?php echo $html->getCrumbs(' > ','Dash Board'); ?>
			<?php
			   if ($session->check('Message.flash')): $session->flash(); endif; // this line displays our flash messages
			   echo $content_for_layout;
			?>	
		</div>			
		<div id="footer">
			<ul>
			<!--<li>METAREP v.0.1.3</li>-->
			</ul>
		</div>
	</div>
	<?php echo $cakeDebug;  ?>
</body>
</html>