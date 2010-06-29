<!----------------------------------------------------------
  File: index.ctp
  Description: Dashboard Index

  Author: jgoll
  Date:   Mar 22, 2010
<!---------------------------------------------------------->

<?php echo $html->css('user-dashboard.css'); ?>
<?php echo $html->css('jquery-ui-1.7.2.custom.css'); ?>

<div class="dash-board">
<h2><?php __('Dash Board - Welcome '); echo Authsome::get('username');?></h2>
<div class="user-dash-board-manage-panel">
<fieldset>
<legend >Manage Account</legend>
<ul>
    <?if (Authsome::check('users/index')):?>
    <li>
        <?=$html->link('Manage Users','/users/index')?>
    </li>
    <?endif;?>
    <?if (Authsome::check('user_group_permissions/index')):?>
    <li>
        <?=$html->link('Manage Permissions','/user_group_permissions/index')?>
    </li>
    <?endif;?>
    <li><?=$html->link('Change Password','/users/change_password')?></li>
    <li><?=$html->link('Logout','/users/logout')?></li>
</ul>
</fieldset>
</div>

<div class="user-dash-board-project-panel">
	<fieldset>
	<legend >Projects</legend>
	
	<div id="accordion">
		<h3><a href="#">Section 1</a></h3>
		<div>
			<p>
			Mauris mauris ante, blandit et, ultrices a, suscipit eget, quam. Integer
			ut neque. Vivamus nisi metus, molestie vel, gravida in, condimentum sit
			amet, nunc. Nam a nibh. Donec suscipit eros. Nam mi. Proin viverra leo ut
			odio. Curabitur malesuada. Vestibulum a velit eu ante scelerisque vulputate.
			</p>
		</div>
		<h3><a href="#">Section 2</a></h3>
		<div>
			<p>
			Sed non urna. Donec et ante. Phasellus eu ligula. Vestibulum sit amet
			purus. Vivamus hendrerit, dolor at aliquet laoreet, mauris turpis porttitor
			velit, faucibus interdum tellus libero ac justo. Vivamus non quam. In
			suscipit faucibus urna.
			</p>
		</div>
		<h3><a href="#">Section 3</a></h3>
		<div>
			<p>
			Nam enim risus, molestie et, porta ac, aliquam ac, risus. Quisque lobortis.
			Phasellus pellentesque purus in massa. Aenean in pede. Phasellus ac libero
			ac tellus pellentesque semper. Sed ac felis. Sed commodo, magna quis
			lacinia ornare, quam ante aliquam nisi, eu iaculis leo purus venenatis dui.
			</p>
			<ul>
				<li>List item one</li>
				<li>List item two</li>
				<li>List item three</li>
			</ul>
		</div>
		<h3><a href="#">Section 4</a></h3>
		<div>
			<p>
			Cras dictum. Pellentesque habitant morbi tristique senectus et netus
			et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in
			faucibus orci luctus et ultrices posuere cubilia Curae; Aenean lacinia
			mauris vel est.
			</p>
			<p>
			Suspendisse eu nisl. Nullam ut libero. Integer dignissim consequat lectus.
			Class aptent taciti sociosqu ad litora torquent per conubia nostra, per
			inceptos himenaeos.
			</p>
		</div>
	</div>
</div>

<div class="user-dash-board-news-panel" > 
	<fieldset>
		<legend >News</legend>
		<?php foreach( $news as $newsItem ) : ?>
		        <?php echo $html->link($newsItem['GosBlog']['title'], $newsItem['GosBlog']['link']); ?><br/>
		        <em><?php echo $newsItem['GosBlog']['pubDate']; ?></em>
		        <hr>
		<?php endforeach; ?>
		<div class="paging">
		        <?php echo $paginator->prev('<< '.__('previous', true), array(), null, array('class'=>'disabled'));?>
		 |      <?php echo $paginator->numbers();?>
		        <?php echo $paginator->next(__('next', true).' >>', array(), null, array('class'=>'disabled'));?>
		</div>
	</fieldset>
</div>


<script type="text/javascript">
jQuery(function() {
	jQuery("#accordion").accordion();
	});
</script>