<div id="flashMessage" class="message">	
	<?php
		echo "There was a problem with fetching data from the Lucene index. Please contact metarep-support@jcvi.org if this problem is persistent)";
		echo "Detailed error message:<span style=\"font-size:0.5em;\>".$message->getTrace()."</span>";
	?>	
</div>