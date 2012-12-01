<!----------------------------------------------------------
  
  File: index.ctp
  Description: User Dashboard
  
  The User Dashboard pages allow users to modify their account
  informationm and change their password. It also lists the 
  projects of which the user is a Project Admin. Useers can 
  adjust the project's user permissions and edit the project
  description. The METAREP Admin user can get a list of all
  registered users and sees all projects. The METAREP Admin
  can edit projects to specify project Admin users.

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

<?php echo $html->css('user-dashboard.css'); ?>
<?php echo $html->css('jquery-ui-1.7.2.custom.css');?>

<div class="user-dash-board">
	<h2><?php __('Dash Board - Welcome '); echo Authsome::get('first_name'); echo(' ');echo Authsome::get('last_name');?></h2>
	
	<div class="user-dash-board-manage-panel" >
		<fieldset >
			<legend >Manage Account</legend>
			<p>
				<ul>
				    <?php
				    $currentUser	=  Authsome::get();
					$currentUserId	= $currentUser['User']['id'];	
					$userGroup  	= $currentUser['UserGroup']['name'];	
					
				    if($userGroup === ADMIN_USER_GROUP) {
				        echo("<li>".$html->link('Manage Users','/users/index'). "</li>");	
				    }
				    
				   	echo("<li>".$html->link('Change Account Information',"/users/edit/$currentUserId"). "</li>");		
				   	echo("<li>".$html->link('Change Password','/users/changePassword'). "</li>");		
				    echo("<li>".$html->link('Logout','/users/logout'). "</li></ul></p></fieldset>");
				    
				    ?>	
	</div>
	
	<?php if(count($projects)> 0) {
		echo("<div class=\"user-dash-board-project-panel\">
		<fieldset>
		<legend >Manage Projects</legend>
		
		<div id=\"accordion\">");
			foreach($projects as $project) {	
					
						echo("<h3><a href=\"#\">{$project['Project']['name']}</a></h3><div><p>	
						<strong>{$project['Project']['description']}</strong>			
							<ul><li>Last Updated {$project['Project']['updated']}</li>
								
													<li>Populations ".count($project['Population'])."</li>
							<li>Libraries ".count($project['Library'])."</li>				
							<ul></p><BR>
							<p>
								<ul>");
							echo("<li>".$html->link('View Project',"/projects/view/{$project['Project']['id']}")."</li>");
							echo("<li>".$html->link('Edit Project Information',"/projects/edit/{$project['Project']['id']}")."</li>");
							echo("<li>".$html->link('Manage Project Permissions',"/users/editProjectUsers/{$project['Project']['id']}" )."</li>	");	
							echo("</li></ul></p>");
									    		
						 	
							echo("</div>");
				}
			
		echo("</div>");
	echo("</div>");}?>
	
	<div class="user-dash-board-feedback-panel"> 
		<fieldset>
		<legend >Feedback</legend>
		<?php 
			echo($form->create('Users', array('action' => 'feedback')));
			echo $form->input( 'type', array( 'options' => array('feature request'=>'feature request','bug report'=>'bug report','data load'=>'data load','other'=>'other'), 'selected' => 'other','label' => false, 'empty'=>'--select feedback type--'));
			echo $form->input('feedback',array('type' => 'textaerea'));
			echo $form->submit('submit');
			echo($form->end()); 
		?>		
	</fieldset>
	</div>
	<?php if (!empty($news)) {
		echo("<div class=\"user-dash-board-news-panel\">  
		<fieldset >
			<legend >News</legend>");
			foreach( $news as $newsItem ) { 
			        echo $html->link($newsItem['Blog']['title'], $newsItem['Blog']['link']); ?><br/>
			        <?php echo "<em>{$newsItem['Blog']['pubDate']}</em><hr>";
			        
			}
		echo("</fieldset>
		</div>");
	}
	?>
</div>

<script type="text/javascript">
	jQuery(function() {
		jQuery("#accordion").accordion({fillSpace: true, event: "mouseover"
			
			});
		});
</script>