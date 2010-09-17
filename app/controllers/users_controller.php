<?php

/***********************************************************
* File: users_controller.php
* Description: Handles all user actions such as changing
* new user registration, account information updates and 
* password resets.
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
* @version METAREP v 1.2.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

define('FEEDBACK_FEATURE_REQUEST','feature request');
define('FEEDBACK_BUG_REPORT','bug report');
define('FEEDBACK_DATA_LOAD','data load');
define('FEEDBACK_OTHER','other');

class UsersController extends AppController {
	
	var $name = 'Users';
	#var $uses = array('User','Project','UserGroup');
	var $uses = array();
	
    function index() {
    	$this->loadModel('User');
    	$this->User->order = 'User.id ASC';
    	$this->User->recursive = 0;
        $users = $this->paginate('User');
        $this->set('users',$users);
    }

    function edit($id=null) {	
    	$this->loadModel('User');
		$this->User->contain('UserGroup');		
    	$currentUser		= $this->Authsome->get();
		$currentUsername 	= $currentUser['User']['username'];	    	
    	
        if (!empty($this->data)) {      
            if ($this->User->save($this->data)) {
            	
                $this->Session->setFlash('Account information has been saved.');
                
                if($currentUsername === 'admin') {
                	$this->redirect('/users/index');
            	}
            	else {
            		$this->redirect('/dashboard');
            	}
            }
            else {
            	 $userGroupId =  $this->data['User']['user_group_id'];
            	 $userGroups = $this->User->UserGroup->find('list');         	
            	 $this->set(compact('userGroups','userGroupId'));
            }
        }
        else {
      		$userGroups = $this->User->UserGroup->find('list');         	
            $this->data = $this->User->read(null,$id);
            $userGroupId =  $this->data['User']['user_group_id'];
            $this->set(compact('userGroups','userGroupId'));
        }
    }
    function feedback() {
    	$this->loadModel('User');
    	$currentUser	= $this->Authsome->get();
		$userName 		= $currentUser['User']['username'];	
		$userEmail 		= $currentUser['User']['email'];	
		$feedbackType 	= $this->data['Users']['type'];
		$feedback		= $this->data['Users']['feedback'];
		$feedback = urldecode($feedback);
		$this->User->sendFeedbackConfirmation($userName,$userEmail,$feedbackType,$feedback);
		$this->Session->setFlash('Thank you very much for your feedback. An email confirmation has been sent to your account.');
		$this->redirect('/dashboard');
    }
    
    function delete($id) {
    	$this->loadModel('User');
        $this->User->delete($id);
        $this->Session->setFlash('User was deleted.');
        $this->redirect('/users/index');
    }
    
	function logout() {
		$this->Authsome->logout();
		$this->Session->setFlash('You are now logged out.');
		$this->redirect('/dashboard');
	}

	function register() {	
		#get user credentials
		$user = $this->Authsome->get();
		
		#if no user exists redirect to dashboard
		if ($user) {
			$this->redirect("/dashboard");
		}
		else {		
			if ($this->data) {
				$this->loadModel('User');
				if ($this->User->save($this->data)) {
					$this->Session->setFlash("Thank you you very much for registering. Please check your email to activate your METAREP account.");
					$this->redirect("/dashboard");
					
				} else {
					$this->data['User']['password'] = null;
					$this->data['User']['confirm_password'] = null;
				}
			}
		}
	}
	
	function activatePassword() {		
		if ($this->data) {
			if (!empty($this->data['User']['ident']) && !empty($this->data['User']['activate'])) {
				$this->loadModel('User');
				$this->set('ident',$this->data['User']['ident']);
				$this->set('activate',$this->data['User']['activate']);

				$return = $this->User->activatePassword($this->data);
				if ($return) {
					$this->Session->setFlash("Your new password has been saved. Please log in.");
					$this->redirect("/dashboard");					
				}
				else {
					$this->Session->setFlash("Your new password could not be saved. Please check your email and click the password reset link again");
					$this->redirect("/dashboard");	
				}
			}
		}
		
		else {			
			if (isset($_GET['ident']) && isset($_GET['activate'])) {
				$this->set('ident',$_GET['ident']);
				$this->set('activate',$_GET['activate']);
			}
		}
	}
	
	function changePassword() { 
		$this->loadModel('User');
		if ($this->data) {			
			if ($this->User->changePassword($this->data)) {
				$this->Session->setFlash("Your password has been changed.");
				$this->redirect("/dashboard");	
			}
		}
		else {
			$userID = $this->Session->read('User.id');
			$this->data = $this->User->read(null,$userID);
			$this->data['User']['password']='';
		}
	}
	
	function editProjectUsers($projectId=null) {
		$this->loadModel('User');
		
		
		$currentUser	= $this->Authsome->get();
		$currentUserId 	= $currentUser['User']['id'];			
		
		if(!empty($this->data)) {	
			$this->loadModel('Project');
			$this->User->contain('ProjectsUser');
			
			$projectId = $this->data['Project']['id'];  
			
			#delete all previous users except current user
			$this->User->ProjectsUser->deleteAll(array('project_id'=>$projectId,'user_id !=' => $currentUserId));
			
			if($this->Project->save($this->data)) {
				$this->Session->setFlash("Your user project permissions have been saved.");
				$this->redirect("/dashboard");	
			}		
			else {
				$this->Session->setFlash("Your new project permissions could not be saved.");
				$this->redirect("/dashboard");	
			}
		}
		else {
			$this->User->Project->contain('User');
			
			#get all users except current user and admin for multi-user select box	
			$users  = $this->User->findAll(array('NOT'=>array('User.username'=>'admin','User.id'=>$currentUserId)));
			
			#get current project users
			$existingUsers = array();
			$projectUsers = $this->User->Project->find('all', array('conditions'=>array('Project.id'=>$projectId)));
			
			$projectName = $projectUsers[0]['Project']['name'];
			
			#format current users as list of user ids 
			foreach($projectUsers[0]['User'] as $projectUser) {
				$projectUserId = $projectUser['id'];
				
				#only add non project admin & admin to existing user list
				if($projectUserId != $currentUserId && $projectUser['username']!='admin') {
					array_push($existingUsers,$projectUserId);
				}
			}	
					
			$projectUsers = $existingUsers;			
			
			$this->set(compact('projectId','projectName','users','projectUsers'));			
		}
	}
    function guestLogin(){
    	
    	$guestUser = array('username'=>'guest',
    					   'password' => 'guest',
    					   'remember' => 0);
    	
    	$user = Authsome::login($guestUser);

    	if (!$user) {				
				$this->Session->setFlash('Unknown user or wrong password');
				$this->redirect('/dashboard');
		}
		
		$this->Session->write("User",$user);
		$this->Session->write("User.id",$user["User"]["id"]);
		$this->Session->write("UserGroup.id",$user["UserGroup"]["id"]);
		$this->Session->write("UserGroup.name",$user["UserGroup"]["name"]);

		$this->redirect('/projects/index');		    	  	
    }
	
	
	function login() {
		$this->loadModel('User');	
		#account activation
		if(isset($_GET["ident"])) {
			#on success
			if ($this->User->activateAccount($_GET)) {
				$this->Session->setFlash("Thank you. Your METAREP account has been activated. Please login.");
				$this->redirect("/dashboard");		
			}
			#on failure 
			else {
				$this->Session->setFlash("There was a problem with your account information. Please contact ".METAREP_SUPPORT_EMAIL);
				$this->redirect("/dashboard");				
				$this->flash("Sorry. There were problems in your account activation.",Configure::read('httpRootUrl').'/users/login');
			}
		}
		#normal login
		else {
			
			if (empty($this->data)) {
				return;
			}
			
			$user = Authsome::login($this->data['User']);
			
			//if authentification failed
			if (!$user) {				
				$this->Session->setFlash('Unknown user or wrong password');
				$this->redirect('/dashboard');
			}

			$remember = (!empty($this->data['User']['remember']));
			
			if($remember) {
				Authsome::persist('2 weeks');
			}
		
			$this->Session->write("User",$user);
			$this->Session->write("User.id",$user["User"]["id"]);
			$this->Session->write("UserGroup.id",$user["UserGroup"]["id"]);
			$this->Session->write("UserGroup.name",$user["UserGroup"]["name"]);

			$this->redirect('/dashboard');
		}
    }

	function forgotPassword() {	
		$this->loadModel('User');	
		if ($this->data) {
			$email = $this->data["User"]["email"];
			if ($this->User->forgotPassword($email)) {
				$this->Session->setFlash('Please check your email.');
				$this->redirect('/dashboard');				
			} else {
				$this->Session->setFlash('Your email is invalid or not registered.');
				$this->redirect('/dashboard');					
			}
		}
	}
}
?>
