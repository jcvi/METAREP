<?php
/***********************************************************
*  File: apis.ctp
*  Description:
*
*  Author: jgoll
*  Date:   May 28, 2010
************************************************************/

echo $html->css('cake.generic.css');
echo $html->link("Back","/projects/view/$projectId",array('class'=>'button-left'));
echo("<p><iframe src=\"$link\" target=\"_blank\"  width=\"100%\"
style=\"height:805px\" marginheight=\"200px\" align=\"center\" scrolling=\"no\"
>[Your browser does <em>not</em> support <code>iframe</code>,
or has been configured not to display inline frames.]</iframe></p>");
?>
