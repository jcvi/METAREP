<!----------------------------------------------------------

  File: index.ctp
  Description: Dashboard Index
  
  The Dashboard Index page allows users to login, register, 
  and reset their password if they have forgotten it. It also
  features general information about METAREP and links to the
  METAREP JCVI blog.

  PHP versions 4 and 5

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.0.1
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->

<?php echo $html->css('dashboard.css'); ?>
<?php echo $html->css('jquery-ui-1.7.2.custom.css'); ?>

<div class="dash-board">
	<h2><?php __('Dash Board')?></h2>
	<div class="dash-board-main-panel">
	<fieldset>
	<legend >JCVI Metagenomics Reports (v. 1.1.0 beta)</legend>
	<div class="dash-board-abstract">	
		<p>JCVI Metagenomics Reports (METAREP) is a new <strong>open source</strong> tool for <strong>high-performance</strong> comparative metagenomics. 
		It provides a suite of web based tools to help scientists to <strong>view, query, browse</strong> and <strong>compare</strong> metagenomics annotation data
		derived from ORFs called on metagenomics reads.</p><BR>
		<p>
		METAREP supports browsing of functional and taxonomic
		assignments. Users can either specify fields,
		or logical combinations of fields to <strong>flexibly filter datasets on the fly</strong>.	
		Users can <strong>compare multiple datasets</strong> at	various functional and taxonomic levels
		applying statistical tests as well as hierarchical clustering, multidimensional scaling and heatmaps.
		</p><BR><p> 
		For each of these features, METAREP provides download options to <strong>export
		tab delimited files</strong> for downstream analysis. The web site is
		optimized to be <strong>user friendly and fast</strong>.</p>
		<BR><p> 
		<table  style="border-style:none !important;"><tr><td style="text-align:center">
			<h6>Download Flyer</h6>
				<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Download Flyer')), '/files/METAREP-flyer.pdf',array('escape' => false)));?>	
			</td >
			<td style="text-align:center">
			<h6>Download Manual</h6>
			<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Download Manual')),'/files/METAREP-manual-v1.1.0.pdf',array('escape' => false)));?>	
			</td>	
			<td style="text-align:center; border-right:none">
			<h6>Open Source</h6>
			<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Open Source')), 'http://github.com/jcvi/METAREP',array('escape' => false)));?>	
			</td>	
		</tr>
		</table>
		</p>			
	</div>
	</fieldset>	
	
		<fieldset>
			<legend >Slideshow</legend>						
			<div id="dash-board-gallery">
				<a href="#" class="show">
					<img src="img/metarep-view.jpg" alt="View Metagenomics Datasets" width="580" height="360" title="" alt="" rel="<h3>View Metagenomics Datasets</h3>"/>
				</a>
				<a href="#">
					<img src="img/metarep-search.jpg" alt="Search Metagenomics Datasets" width="580" height="360" title="" alt="" rel="<h3>Search Metagenomics Datasets</h3>"/>
				</a>
				
				<a href="#">
					<img src="img/metarep-browse.jpg" alt="Browse Metagenomics Datasets" width="580" height="360" title="" alt="" rel="<h3>Browse Metagenomics Datasets</h3>"/>
				</a>
			
				<a href="#">
					<img src="img/metarep-compare.jpg" alt="Compare Metagenomics Datasets" width="580" height="360" title="" alt="" rel="<h3>Compare Metagenomics Datasets</h3>"/>
				</a>
				
				<a href="#">
					<img src="img/metarep-download.jpg" alt="Export Tab Delimited Files" width="580" height="360" title="" alt="" rel="<h3>Export Tab Delimited Files</h3> "/>
				</a>
				<div class="caption"><div class="content"></div>
			</div>	
	</fieldset>
</div>

<div class="dash-board-login-panel"> 
<fieldset>
<legend >Login </legend>
	<?php  
	#die(phpinfo());
	echo $form->create('User', array('action' => 'login')); 
	echo $form->input("username");	
	echo $form->input("password",array("type"=>"password"));
	echo $form->input('remember', array('label' => "Remember me for 2 weeks",'type' => 'checkbox'));
	echo $form->submit('Login'); 
	echo $form->end(); 
	echo('<p>');
	echo $html->link("Forgot password?","/users/forgotPassword");
	echo('</p>');
	echo('<p>');
	echo $html->link("REGISTER","/users/register",array('class'=>'button')); 
	echo $html->link("TRY IT","/users/guestLogin",array('class'=>'button')); 
	echo('</p>');
	
	?>
</fieldset>
</div>


<?php if (!empty($news)):?>
<div class="dash-board-news-panel" > 
	<fieldset >
		<legend >News</legend>
		<?php foreach( $news as $newsItem ) : ?>
		        <?php echo $html->link($newsItem['Blog']['title'], $newsItem['Blog']['link']); ?><br/>
		        <em><?php echo $newsItem['Blog']['pubDate']; ?></em>
		        <hr>
		<?php endforeach; ?>
	</fieldset>
</div>
<?php endif;?>

<script type="text/javascript">
jQuery(function() {
	jQuery("#accordion").accordion({fillSpace: true, event: "mouseover"
		
		});
	});
</script>

<script type="text/javascript">

jQuery(document).ready(function() {		
	
	//Execute the slideShow
	slideShow();

});

function slideShow() {

	//Set the opacity of all images to 0
	jQuery('#dash-board-gallery a').css({opacity: 0.0});
	
	//Get the first image#1DCCEF and display it (set it to full opacity)
	jQuery('#dash-board-gallery a:first').css({opacity: 1.0});
	
	//Set the caption background to semi-transparent
	jQuery('#dash-board-gallery .caption').css({opacity: 1});

	//Resize the width of the caption according to the image width
	jQuery('#dash-board-gallery .caption').css({width: jQuery('#dash-board-gallery a').find('img').css('width')});
	
	//Get the caption of the first image from REL attribute and display it
	jQuery('#dash-board-gallery .content').html(jQuery('#dash-board-gallery a:first').find('img').attr('rel'))
	.animate({opacity: 0.7}, 400);
	
	//Call the gallery function to run the slideshow, 6000 = change to next image after 6 seconds
	setInterval('gallery()',6000);
	
}

function gallery() {
	
	//if no IMGs have the show class, grab the first image
	var current = (jQuery('#dash-board-gallery a.show')?  jQuery('#dash-board-gallery a.show') : jQuery('#dash-board-gallery a:first'));

	//Get next image, if it reached the end of the slideshow, rotate it back to the first image
	var next = ((current.next().length) ? ((current.next().hasClass('caption'))? jQuery('#dash-board-gallery a:first') :current.next()) : jQuery('#dash-board-gallery a:first'));	
	
	//Get next image caption
	var caption = next.find('img').attr('rel');	
	
	//Set the fade in effect for the next image, show class has higher z-index
	next.css({opacity: 0.0})
	.addClass('show')
	.animate({opacity: 1.0}, 1000);

	//Hide the current image
	current.animate({opacity: 0.0}, 1000)
	.removeClass('show');
	
	//Set the opacity to 0 and height to 1px
	jQuery('#dash-board-gallery .caption').animate({opacity: 0.0}, { queue:false, duration:0 }).animate({height: '1px'}, { queue:true, duration:300 });	
	
	//Animate the caption, opacity to 0.7 and heigth to 100px, a slide up effect
	jQuery('#dash-board-gallery .caption').animate({opacity: 1	},100 ).animate({height: '100px'},500 );
	
	//Display the content
	jQuery('#dash-board-gallery .content').html(caption);
	
	
}
</script>
