<?php
/***********************************************************
* File: user.php
* Description: User Model
*
* PHP versions 4 and 5
*
* METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
* Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @link http://www.jcvi.org/metarep METAREP Project
* @package metarep
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class User extends AppModel {
	
	var $name 				 = 'User';
	var $recursive 			 = 1;
	var $actsAs 			 = array('Membership'=>array('option1'=>'value'));
	var $hasAndBelongsToMany = array('Project'=>array('joinTable'=>'projects_users'));
    var $hasMany 			 = array('LoginToken');
	var $belongsTo 			 = array('UserGroup');
	
	var $displayField 		= 'username';
	
	var $validate = array(
				"username" => array(
					'mustUnique'=>array(
						'rule' => array('isUnique'),
						'message' => 'username is already taken.'),
					'mustBeLonger'=>array(
						'rule' => array('minLength', 3),
						'message'=> 'username is required and must have a minimum of 3 alphanumeric characters.',
						'last'=>true),
					'mustHaveNoSpecial'=>array(
						'rule' => 'alphaNumericDashUnderscore',
						'message' => 'username can only be letters, numbers, dash and underscore.'),
						),
				'email'=> array(
					'mustBeEmail'=> array(
						'rule' => array('email', false),
						'message' => 'Please supply a valid email address.',
						'last'=>true),
					'mustUnique'=>array(
						'rule' =>'isUnique',
						'message' =>'email address is already registered.',
						)
					),
				'confirm_password'=>array(
						'mustBeLonger'=>array(
							'rule' => array('minLength', 4),
							'message'=> 'Please fill in and confirm your password.',
						),
						'mustMatch'=>array(
							'rule' => array('verifies', 'password'),
							'message' => 'Passwords do not match.'
						)
				),
				'captcha'=>array(
							'rule' => 'notEmpty',
							'message' => 'This field cannot be left blank'
						),
				'first_name'=>array(
							'rule' => 'notEmpty',
							'message' => 'This field cannot be left blank'
						),
				'last_name'=>array(
							'rule' => 'notEmpty',
							'message' => 'This field cannot be left blank'
				)					
			);
	/**
	 * Set user privileges before saving the new user. If the user's name equals
	 * admin, the user group id is set to 1 (ADMIN_USER). If the user's email
	 * extension matches the internal email extension, the user's group id is 
	 * set to 4 (INTERNAL_USER). Internal users have access to all datasets. The
	 * internal email extension (INTERNAL_EMAIL_EXTENSION) can be defined in the
	 * METAREP configuration file (app/config/metarep.php). If neither macthes,
	 * the user group is set to 2 (EXTERNAL_USER). External users can only access
	 * project datasets for which they have permissions.
	 * 
	 * @return void
	 * @access public
	 */
	function beforeSave() 	{
		if(!empty($this->data)) {						
			$email  =  $this->data['User']['email'];
			$tmp = split('@',$email);
			$emailExtension = $tmp[1];

			//set admin privileges based on user name
			if($this->data['User']['username'] === 'admin') {
				//the user's group id is set to 1 (ADMIN_USER).
				 $this->data['User']['user_group_id'] = 1;
			}			
			//set internal uprivileges based on email extension
			else if(defined('INTERNAL_EMAIL_EXTENSION')) {
				if($emailExtension === INTERNAL_EMAIL_EXTENSION) {
					//set user group to INTERNAL USER GROUP (4)
					$this->data['User']['user_group_id'] = 4;
				}
			}
			//set external privileges
			else {
				$this->data['User']['user_group_id'] = 2;
			}
		}	
		return true;
	}
			
			
	function afterSave($created) {
		if ($created) {
			$this->sendRegistrationEmail();
		}
	}
	
    function hashPasswords($data) {
		if (!isset($data['User']['confirm_password']))
		{
			if (isset($data['User']['password'])) {
				$data['User']['password'] = md5($data['User']['password']);
				return $data;
			}
		}
        return $data;
    }
	function authsomeLogin($type, $credentials = array()) {
		
		//project information is not needed for login
		$this->unbindModel(array('hasAndBelongsToMany' => array('Project'),),false);	
		
		switch ($type) {
			case 'guest':
				// You can return any non-null value here, if you don't
				// have a guest account, just return an empty array
				return array();
			case 'credentials':
				$password = md5($credentials['password']);

				// This is the logic for validating the login
				$conditions = array(
					'User.username' => $credentials['username'],
					'User.password' => $password,
                    'User.active' => '1'
				);
				break;
			case 'cookie':
				list($token, $userId) = split(':', $credentials['token']);
				$duration = $credentials['duration'];

				$loginToken = $this->LoginToken->find('first', array(
					'conditions' => array(
						'user_id' => $userId,
						'token' => $token,
						'duration' => $duration,
						'used' => false,
						'expires <=' => date('Y-m-d H:i:s', strtotime($duration)),
					),
					'contain' => false
				));

				if (!$loginToken) {
					return false;
				}

				$loginToken['LoginToken']['used'] = true;
				$this->LoginToken->save($loginToken);

				$conditions = array(
					'User.id' => $loginToken['LoginToken']['user_id']
				);
            break;

			default:
				return null;
		}

        return $this->find('first', compact('conditions'));
    }

	function authsomePersist($user, $duration) {
		$token = md5(uniqid(mt_rand(), true));
		$userId = $user['User']['id'];

		$this->LoginToken->create(array(
			'user_id' => $userId,
			'token' => $token,
			'duration' => $duration,
			'expires' => date('Y-m-d H:i:s', strtotime($duration)),
		));
		$this->LoginToken->save();

		return "${token}:${userId}";
	}
	
	#validation rule
    function alphaNumericDashUnderscore($check) {
      // $data array is passed using the form field name as the key
      // have to extract the value to make the function generic
      $value = array_values($check);
      $value = $value[0];

      return preg_match('|^[0-9a-zA-Z_-]*$|', $value);
    }

	function verifies($data, $field) {
		$value = Set::extract($data, "{s}");
		return ($value[0] == $this->data[$this->name][$field]);
	}
}
?>