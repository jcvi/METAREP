<!----------------------------------------------------------
  File: jcvi.ctp
  Description: JCVI Layout

  Author: jgoll
  Date:   May 12, 2010
<!---------------------------------------------------------->

<?php

#require_once('/usr/local/common/web'.$_SERVER['WEBTIER'].'/templates/smarty/template/class.template.php');
	require_once("/usr/local/common/web".$_SERVER['WEBTIER']."/templates/class.template.php");

	$template = new template();
	$template->assign('page_header', null);
	$template->assign('stylesheets', null);
	$template->assign('javascript', null);
	$template->assign('main_content', $content_for_layout);
	$template->assign('project_name', "METAREP");
	$template->assign('breadcrumb', "");
	$template->assign('search_site', "");
	$template->display('2_column_fluid_width_full_screen.tpl');?>
</body>
</html>