<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

class WizardLib extends TikiLib
{
	/**
	 * onLogin is the collection point for all login events, which may run a wizard.
	 * All triggers at login time, must call this function.
	 * 
	 * The function will not return, if the wizard is run.
	 * Instead it will redirect to the specified homePageUrl
	 * 
	 * Use the start functions, e.g. startAdminWizard, to start the wizard manually, not this function
	 *
	 * @param mixed $user The logged in user
	 * @param mixed $homePageUrl The return URL
	 * @param mixed $force Force the opening of the wizard
	 * @return none
	 *
	 */
	public function onLogin($user, $homePageUrl, $force = false)
	{
		global $base_url, $userlib;
		
		$openWizard = false;
		
		if ($force) {
			// Force opening of the wizard
			// Needed for fresh-/re-installs, when admin is an anonymous user at check time
			$openWizard = true;
			
		} else {
			// Check the user status
			$isAdmin = $userlib->user_has_permission($user, 'tiki_p_admin');
		
			// Check if the wizard should be opened
			$activeLoginWizard = $this->get_preference('wizard_admin_hide_on_login') !== 'y';
			if ($isAdmin && $activeLoginWizard) {
				$openWizard = true;
			}
		}
		
		if ($openWizard) {	
			// User is an admin
			$this->startAdminWizard($homePageUrl,0);
		} else {
			// A regular user
			// New User wizard is not implemented
		}
	}
	
	/**
	 *	startAdminWizard - Manually start the admin wizard
	 *	Can also be started by going to: tiki-wizard_admin.php?url=tiki-admin.php?page=general#content2
	 *	The url value, is where the wizard should return, when it's done
	 * 
	 *  @param	$homePageUrl	The url to return to, when the wizard is done
	 *  @param	$stepNr			Which step in the wizard to go to. Default = 0
	 *  @return		This function doesn't return
	 */
	public function startAdminWizard($homePageUrl, $stepNr=0)
	{
		// Start the admin wizard
		$url = $base_url.'tiki-wizard_admin.php?&stepNr=' . $stepNr . '&url=' . rawurlencode($homePageUrl);
		$accesslib = TikiLib::lib('access');
		$accesslib->redirect($url);
	}
	
	/**
	*	Wizard's page stepping logic
	*	Every page added must be a subclass of the Wizard class
	*	@param	array	of Wizard pages
	*/
	public function showPages($pages)
	{
		global	$smarty;
		
		if (!isset($_REQUEST['url'])) {
			$smarty->assign('msg', tra("No return URL specified"));
			$smarty->display("error.tpl");
			die;
		}
		if (empty($pages)) {
			$smarty->assign('msg', tra("No wizard pages specified"));
			$smarty->display("error.tpl");
			die;
		}

		// Assign the return URL
		$homepageUrl = $_REQUEST['url'];
		$smarty->assign('homepageUrl', $homepageUrl);


		// User pressed "Close". 
		//	Save the "Show on login" setting, and no other preferences
		if (isset($_REQUEST['close'])) {
			
			// Save "Show on login" setting
			$showOnLogin = ( isset($_REQUEST['showOnLogin']) && $_REQUEST['showOnLogin'] == 'on' ) ? 'y' : 'n';
			$this->showOnLogin($showOnLogin);
			
			//	Then exit, by returning the specified URL
			$accesslib = TikiLib::lib('access');
			$accesslib->redirect($homepageUrl);
		}


		$isFirstStep = !isset($_REQUEST['wizard_step']);
		$isUserStep = isset($_REQUEST['stepNr']);	// User defined step nr
		if ($isUserStep) {
			$stepNr = intval($_REQUEST['stepNr']);
		} else {
			$stepNr = intval($_REQUEST['wizard_step']);
		}

		$stepBack = false;
		if (isset($_REQUEST['back'])) {
			// Discard changes on page
			//	Go to previous page
			$stepNr -= 1;
			$stepBack = true;
		}
		
		// Validate the specified stepNr
		if (($stepNr < 0) || ($stepNr >= count($pages))) {
			$smarty->assign('msg', tra("Invalid wizard stepNr specified"));
			$smarty->display("error.tpl");
			die;
		}
		
		if (!$stepBack && !$isFirstStep || ($isUserStep && $stepNr > 0)) {
			
			// Commit the step just completed
			$pages[$stepNr]->onContinue();
			
			// Loop until the next displayed wizard page
			//	Return when all pages have been processed.
			do {
				$next = true;
				if (count($pages) > $stepNr+1) {
					$stepNr += 1;
					if (count($pages) == $stepNr+1) {
						$smarty->assign('lastWizardPage', 'y');
					}
					
					// If onSetupPage returns true, processing should continue
					$show = $pages[$stepNr]->onSetupPage($homepageUrl);
					
					// Do not show page, if it doesn't return a boolean
					if ($show === true) {
						$next = false;
						break;
					}
					
				} else {
					// Return to homepage, when we get to the end
					$accesslib = TikiLib::lib('access');
					$accesslib->redirect($homepageUrl);
				}
			} while ($next);
		} else {
			// For directly accessed wizard pages scroll back, when not displayed
			do {
				$next = true;
				$show = $pages[$stepNr]->onSetupPage($homepageUrl);

				// Do not show page, if it doesn't return a boolean
				if ($show === true) {
					$next = false;
				}

				if ($stepNr == 0) {
					$smarty->assign('firstWizardPage', 'y');
					$next = false;
				} elseif ($show === false) {
					// Step back			
					$stepNr -= 1;
				}
				
			} while ($next);
		}

		$showOnLogin = $this->get_preference('wizard_admin_hide_on_login') !== 'y';
		$smarty->assign('showOnLogin', $showOnLogin);

		$smarty->assign('wizard_step', $stepNr);
	}
	
	public function showOnLogin($showOnLogin)
	{
		$hide = $showOnLogin === 'y' ? 'n' : 'y';
		$this->set_preference('wizard_admin_hide_on_login', $hide);
	}
}
