<?php

	classreq('traits/scripts/URLs.php');
	classreq('scripts/view.php');

	class login extends view {
		use DBFunctions;
		use URLs;
		use SimpleForms;
		use SimpleORM;
		
			// Security Data
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
			// Login
			//
			// Just show the login screen, i.e., username/password.
			//
			// ---------------------------------------------
		
		public function Display() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			return TRUE;
		}
		
			// Authenticate
			//
			// Actually authenticate a login attempt.
			//
			// ---------------------------------------------
		
		public function Authenticate() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			if($this->Param('google_token_id')) {
					// Google Login?  We must already be authenticated, hook it up.
				$this->login_results = [
					'status'=>'Success',
					'useraccount'=>$this->handler->authentication->user_session,
				];
				return TRUE;
			}
			
			$username = $this->param('username');
			$password = $this->param('password');
			
			$login_args = [
				'username'=>$username,
				'password'=>$password,
			];
			
			$this->login_results = $this->handler->authentication->Login($login_args);
			
			return TRUE;
		}
		
		public function HTMLHeadDisplayExtra_HTTPEquivalents() {
			$redirect_url = '';
			
			if($this->login_results['status'] === 'Success') {
				$redirect = $this->param('redirect');
				
				if($this->validateRedirect(['url'=>$redirect])) {
					$redirect_url = $redirect;
				} else {
					$user_admin_account = $this->login_results['useraccount'];
					
					if($user_admin_account['UserAdmin.id']) {
						$redirect_url = 'master-c.' . $this->script_extension;
					} else {
						$redirect_url = 'user-panel.' . $this->script_extension;
					}
				}
			} elseif($this->login_results['status'] === 'Failure') {
				$redirect_url = 'login.' . $this->script_extension .
						'?failure=1' ;
			}
			
			if($redirect_url) {
				print("\n\t");
				print('<meta http-equiv="refresh" content="3; url=' . $redirect_url . '">');
			}
			
			return TRUE;
		}
		
				// ** HTML FORMAT DATA ** //
		
			// Title
		
		public function GetHTMLFormatData_Title() {
			return 'Login to ' . $this->master_record['Code'];
		}
	}

?>