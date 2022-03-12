<?php

return array(
	//
	// Admin only
	//
	'integrate_admin_include' => '$sourcedir/hook_StickyTopicsOrder.php',//load integrations file

	// Add areas (subsections, etc...) to admin menu
	// Admin.php:AdminMain()
	'integrate_admin_areas' => 'hook_StickyTopicsOrder_admin',

	// Personal subaction-page in ModSettings area (area=modsettings;sa=StickyTopicsOrder)
	// ManageSettings.php:ModifyModSettings()
	// @hint load mod subaction page this
	'integrate_modify_modifications' => 'hook_StickyTopicsOrder_modify',
);
