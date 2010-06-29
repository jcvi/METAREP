<?php
class GoGraph extends AppModel {
	var $useDbConfig = 'go'; 
	var $name = 'GoGraph';
	var $useTable = 'graph_path';
	var $primaryKey = 'id';
	
	var $recursive=0;
	
    var $belongsTo = array(
        'Ancestor' => array(
            'className'    => 'GoTerm',
            'foreignKey' => 'term1_id',
            'dependent'    => true
        ),
        'Descendant' => array(
            'className'    => 'GoTerm',
            'foreignKey' => 'term2_id',
            'dependent'    => true
        )
    );
}
?>