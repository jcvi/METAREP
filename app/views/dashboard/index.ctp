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
  @version METAREP v 1.4.0
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->

<?php echo $html->css('dashboard.css'); ?>
<?php echo $html->css('jquery-ui-1.7.2.custom.css'); ?>


<div class="dash-board">
	<h2><?php __('Dash Board')?></h2>
	<div class="dash-board-main-panel">
	<fieldset >
	<legend >JCVI Metagenomics Reports (v<?php echo(METAREP_VERSION)?>)</legend>
	<div class="dash-board-abstract">	
		<p>JCVI Metagenomics Reports (METAREP) is an <strong>open source</strong> tool for <strong>high-performance</strong> comparative metagenomics. 
		It helps scientists to <strong>view, query, browse</strong> and <strong>compare</strong> metagenomics annotation profiles from short reads or assemblies.
		METAREP supports fielded search using combinations of functional and taxonomic fields to <strong>slice and dice big datasets in real-time</strong>.	
		Users can <strong>compare multiple datasets</strong> at	various functional and taxonomic levels
		applying <strong>statistical tests</strong>  as well as hierarchical clustering, multidimensional scaling and heatmaps.
		
		For each of these features, METAREP provides download options to <strong>export
		tab delimited files</strong> for downstream analysis. The web site is
		optimized to be <strong>user friendly and fast</strong>.</p>
		<BR><p> 
		<table  style="border-style:none !important;"><tr><td style="text-align:center;width:80px">
			<h6>Flyer</h6>
				<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Flyer')), 'http://github.com/downloads/jcvi/METAREP/METAREP-flyer.pdf',array('escape' => false,"class"=>'track_stats',"id"=>'download-flyer')));?>	
			</td >
			<td style="text-align:center; width:80px">
			<h6>Manual</h6>
			<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Manual')),"http://github.com/downloads/jcvi/METAREP/METAREP-1.2.0-beta-manual.pdf",array('escape' => false,"class"=>'track_stats',"id"=>'download-manual')));?>	
			</td>	
			<td style="text-align:center; width:80px">
			<h6>Open Source</h6>
			<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Source')), 'http://github.com/jcvi/METAREP',array('escape' => false,'target' => '_blank',"class"=>'track_stats',"id"=>'download-source')));?>	
			</td>	
			<td style="text-align:center; width:80px">
			<h6>Publication</h6>
			<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Publication')), 'http://bioinformatics.oxfordjournals.org/content/26/20/2631.full.pdf+html',array('escape' => false,'target' => '_blank',"class"=>'track_stats',"id"=>'download-publication')));?>	
			<td style="text-align:center; border-right:none; width:120px">
			<h6>Open Virtualization Format</h6>
			<?php echo $html->div('comparator-download', $html->link($html->image("download-medium.png",array("title" => 'Open Virtualization Format')), 'ftp://ftp.jcvi.org/pub/software/metarep/vm/metarep-v1.2.0-i386.tgz',array('escape' => false,'target' => '_blank',"class"=>'track_stats',"id"=>'download-ovf')));?>	

			</td>				
		</tr>
		</table>
		</p>			
	</div>
	
	</fieldset>	
		<fieldset>
			<legend>Videos</legend>
			
	<div id="tabs">
	<ul>
		<li><a href="#tabs-1">5 Minute Overview</a></li>
		<li><a href="#tabs-2">Demo</a></li>
		<li><a href="#tabs-3">Implementation</a></li>
	</ul>
	<div id="tabs-1">
		<iframe width="565" height="385" theme="light" showsearch="1" src="http://www.youtube.com/v/DJNjM7LMVWU?&amp;hl=en_US&amp;hd=1;theme=light;color=orange;showsearch=1;frameborder=1;egm=1" allowfullscreen modestbranding></iframe>
	</div>
	<div id="tabs-2">
		<iframe width="565" height="385" theme="light" showsearch="1" src="http://www.youtube.com/v/7FPJaPyLjMk?&amp;hl=en_US&amp;hd=1;theme=light;color=orange;showsearch=1;frameborder=1;egm=1" allowfullscreen modestbranding></iframe>
	</div>
	<div id="tabs-3">
		<iframe width="565" height="385" theme="light" showsearch="1" src="http://www.youtube.com/v/j0rlTIkvfvI?&amp;hl=en_US&amp;hd=1;theme=light;color=orange;showsearch=1;frameborder=1;egm=1" allowfullscreen modestbranding></iframe>
	</div>
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
	echo $html->link("REGISTER","/users/register",array('class'=>'button track_stats',"id"=>'register')); 
	echo $html->link("TRY IT","/users/guestLogin",array('class'=>'button track_stats',"id"=>'try-it')); 
	echo('</p>');
	
	?>
</fieldset>
</div>

<div class="dash-board-google-groups-panel" > 
<fieldset >
		<legend >METAREP Mailing List</legend>
			<table border=0 style="background-color: #fff; padding: 0px;" cellspacing=0>			  
			  </td></tr>
			  <form action="http://groups.google.com/group/metarep/boxsubscribe">
			  <tr><td style="padding-left: 2px;padding-right: 10px;">
			  Enter Your Email: <input type=text name=email width=30px>
			  <input type=submit name="sub" value="Subscribe" width="10px">
			  </td></tr>
			</form>
			</table>
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

<div class="dash-board-powered-by-panel" >
	<fieldset >
		<legend>Powered By</legend>
			<?php echo $html->link($html->image("solr.jpg",array("title" => 'Solr','class'=>'img-alignment')), 'http://lucene.apache.org/solr',array('escape' => false,'target' => '_blank'));?>	
			<?php echo $html->link($html->image("cake.png",array("title" => 'CakePHP','class'=>'img-alignment')), 'http://cakephp.org/',array('escape' => false,'target' => '_blank'));?>		

</fieldset>
</div>

<div class="dash-board-share-panel" >

<!-- AddThis Button BEGIN -->
<div class="addthis_toolbox addthis_default_style">
<fieldset >
		<legend >Share</legend>
<a class="addthis_button_email"></a>
<a class="addthis_button_facebook"></a>
<a class="addthis_button_twitter"></a>
<a class="addthis_button_linkedin"></a>
<span class="addthis_separator">|</span>
<a href="http://www.addthis.com/bookmark.php?v=250&amp;username=xa-4c98e7150949e930"   style="text-decoration:none;" class="addthis_button">More</a>
</fieldset>
</div>

<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#username=xa-4c98e7150949e930"></script>
<!-- AddThis Button END -->
</div>


<script type="text/javascript">
jQuery(function() {
	jQuery("#accordion").accordion({fillSpace: true, event: "mouseover"
		
		});
	});
</script>

<script type="text/javascript">

jQuery('a[href$="guestLogin"]').qtip({
	   content: 'Try METAREP. Discover its capabilities by analyzing public METAREP datasets.',
	   style: 'mystyle' });
jQuery('a[href$="register"]').qtip({
	   content: 'Create your own METAREP account to receive updates, analyze public METAREP datasets, and conduct collaborative data analysis for projects to which you have access.',
	   style: 'mystyle' });
jQuery('a[href$="forgotPassword"]').qtip({
	   content: 'Forgot your password? Click this option to enter you email address. A link will be sent to your email address to reset it.',
	   style: 'mystyle' });

jQuery(document).ready(function(){
	jQuery('a.track_stats').click(function() {
		jQuery.post("/metarep/users/stats",{id: jQuery(this).attr('id')});
		return true;
	});
	
jQuery(function() {
		jQuery("#tabs").tabs({ spinner: '<img src="/metarep/img/ajax.gif\"/>' });
		 
	});
});

</script>
