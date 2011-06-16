<?php


/**
*@author Nicolaas [at] sunnysideup.co.nz
*
**/


Director::addRules(50, array(
	'formfields/datefield//$Action/$Value' => 'SimpleDateField_Validator',
));
//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START datefield MODULE ----------------===================

//===================---------------- END datefield MODULE ----------------===================

