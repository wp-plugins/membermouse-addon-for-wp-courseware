<?php
/*
 * Plugin Name: WP Courseware - MemberMouse Add On
 * Version: 1.0
 * Plugin URI: http://flyplugins.com
 * Description: The official extension for <strong>WP Courseware</strong> to add support for the <strong>MemberMouse membership plugin</strong> for WordPress.
 * Author: Fly Plugins
 * Author URI: http://flyplugins.com
 */
/*
 Copyright 2013 Fly Plugins - LightHouse Media, LLC

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */


// Main parent class
include_once 'class_members.inc.php';

// Hook to load the class
// Set to priority of 1 so that it works correctly with MemberMouse
// that specifically needs this to be a priority of 1.
add_action('init', 'WPCW_Members_MemberMouse_init', 1);


/**
 * Initialise the membership plugin, only loaded if WP Courseware 
 * exists and is loading correctly.
 */
function WPCW_Members_MemberMouse_init()
{
	$item = new WPCW_Members_MemberMouse();
	
	// Check for WP Courseware
	if (!$item->found_wpcourseware()) {
		$item->attach_showWPCWNotDetectedMessage();
		return;
	}
	
	// Not found the membership tool
	if (!$item->found_membershipTool()) {
		$item->attach_showToolNotDetectedMessage();
		return;
	}
	
	// Found the tool and WP Coursewar, attach.
	$item->attachToTools();
}


/**
 * Membership class that handles the specifics of the MemberMouse WordPress plugin and
 * handling the data for levels for that plugin.
 */
class WPCW_Members_MemberMouse extends WPCW_Members
{
	const GLUE_VERSION  	= 1.00; 
	const EXTENSION_NAME 	= 'MemberMouse';
	const EXTENSION_ID 		= 'WPCW_members_membermouse';
	
	
	
	/**
	 * Main constructor for this class.
	 */
	function __construct()
	{
		// Initialise using the parent constructor 
		parent::__construct(WPCW_Members_MemberMouse::EXTENSION_NAME, WPCW_Members_MemberMouse::EXTENSION_ID, WPCW_Members_MemberMouse::GLUE_VERSION);
	}
	
	
	
	/**
	 * Get the membership levels for this specific membership plugin. (id => array (of details))
	 */
	protected function getMembershipLevels()
	{
		$levelData = MM_Bundle::getBundlesList();
		if (!empty($levelData))
		{
			$levelDataStructured = array();
			
			// Format the data in a way that we expect and can process
			foreach ($levelData as $bundleID => $bundleName)
			{
				$levelItem = array();
				$levelItem['name'] 	= $bundleName;
				$levelItem['id'] 	= $bundleID;
				$levelItem['raw'] 	= array($bundleID => $bundleName);
								
				$levelDataStructured[$bundleID] = $levelItem;
			}
			
			return $levelDataStructured;
		}
		
		return false;
	}

	
	/**
	 * Function called to attach hooks for handling when a user is updated or created.
	 */	
	protected function attach_updateUserCourseAccess()
	{
		// Events called whenever the user levels are changed, which updates the user access.
		add_action('mm_member_add', 				array($this, 'handle_updateUserCourseAccess'), 10, 1);
		add_action('mm_member_membership_change', 	array($this, 'handle_updateUserCourseAccess'), 10, 1);
		add_action('mm_member_status_change	', 		array($this, 'handle_updateUserCourseAccess'), 10, 1);
		add_action('mm_member_delete', 				array($this, 'handle_updateUserCourseAccess'), 10, 1);
		add_action('mm_bundles_add', 				array($this, 'handle_updateUserCourseAccess'), 10, 1);
		add_action('mm_bundles_status_change', 		array($this, 'handle_updateUserCourseAccess'), 10, 1);
	}
	

	/**
	 * Function just for handling the membership callback, to interpret the parameters
	 * for the class to take over.
	 * 
	 * @param Array $memberDetails The details if the user being changed.
	 */
	public function handle_updateUserCourseAccess($memberDetails)
	{
		// Get all of the levels for this user.
		$user = new MM_User($memberDetails['member_id']);
		
		$userLevels = array();
		
		if ($user->isValid())	
		{	
			// ### 1 - Only get active bundles
			$appliedBundles = $user->getAppliedBundles();

			if (!empty($appliedBundles))
			{
				foreach($appliedBundles as $appliedBundle)	
				{	
					// Generate a list of the bundle IDs to apply.
					$userLevels[] = $appliedBundle->getBundleId();	
				}
			}
			
		}
		
		// Over to the parent class to handle the sync of data.
		parent::handle_courseSync($memberDetails['member_id'], $userLevels);
	}
	
	
	/**
	 * Detect presence of the membership plugin.
	 */
	public function found_membershipTool()
	{
		return class_exists('MM_Bundle');
	}
	
	
}


?>