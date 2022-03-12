<?php
/**
 * *****************************************************************************
 * MaestroSite.ru default installer v.0.3

  ATTENTION: If you are trying to INSTALL this package, please access
  it directly, with a URL like the following:
  http://www.yourdomain.tld/forum/install.php (or similar.)

 * *****************************************************************************
 */
if (!defined('SMF'))
{
	$dir = dirname(__FILE__);
	// If SSI.php is in the same place as this file,
	// and SMF isn't defined, this is being run standalone.
	if (file_exists($dir . '/SSI.php'))
		require_once( $dir . '/SSI.php');
	// one more...
	elseif (file_exists(dirname($dir) . '/SSI.php'))
		require_once( dirname($dir) . '/SSI.php');
	// subfolder in /Packages...?
	elseif (file_exists(dirname(dirname($dir)) . '/SSI.php'))
		require_once( dirname(dirname($dir)) . '/SSI.php');
	// Hmm... no SSI.php and no SMF?
	else
		die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	if (!defined('SMF'))
		die('!!! ALARM !!!');
}

$fname = dirname( __FILE__ ) . '/settings.php';
if( empty($context['uninstalling']) and is_file($fname) and is_readable($fname) )
{
	$settings = require $fname;
	$sets = isset($settings['update']) && is_array($settings['update']) ? $settings['update'] : array();
	if( isset($settings['default']) and is_array($settings['default']) )
		foreach( $settings['default'] as $k => $v )
			if( !isset($modSettings[$k]) )
				$sets[$k] = $v;
	updateSettings($sets);
}

//SMF 2.x only
$fname = dirname( __FILE__ ) . '/hooks.php';
if( is_file($fname) and is_readable($fname) )
{
	$hooks = require $fname;
	$call = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';
	if( is_array($hooks) and !empty($hooks) )
		foreach ($hooks as $hook => $function)
			$call($hook, $function);
}

if (SMF == 'SSI')
	echo '<b>Your settings have been entered into the database!</b><br />';
elseif( !empty($context['uninstalling']) )
{
	$context['template_layers'][] = $tmp = 'hook_uninstalled';
	$context['html_headers'] .= '<style type="text/css">
p.maestrosite_install{margin:1em;text-align:center}
form.maestrosite_uninstall{margin:1em}
form.maestrosite_uninstall ul{padding:0;list-style-type:none}
</style>';
	$context['maestrosite_modname'] = isset($packageInfo['id']) ? urlencode($packageInfo['id']) . '-' : 'noid-';
	$context['maestrosite_modname'] .= isset($packageInfo['version']) ? urlencode($packageInfo['version']) : 'noversion';
	function template_hook_uninstalled_above()
	{
		global $context;
		$reasons = array(
			'Does not work correctly',
			'No required functionality',
			'Not interested',
			'Conflict with other modifications',
			'other: <input name="t" />',
		);
		echo '<form action="http://maestrosite.ru/smf-mod-reason/" method="get" class="maestrosite_uninstall">',
			'<input type="hidden" name="modname" value="', $context['maestrosite_modname'],
			'" />Please, answer the question. For what reason you have removed the modification?<ul>';
		foreach( $reasons as $k => $v )
			echo '<li><input type="checkbox" name="r[', $k, ']" value="', $k, '"/>', $v, '</li>';
		echo '</ul><input type="submit"/></form>';
	}
	function template_hook_uninstalled_below()
	{
	}
}
?>