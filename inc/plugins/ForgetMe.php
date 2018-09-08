<?php

/**
 * @author stefan lenders
 * @copyright 2018
 */

// Make sure we can't access this file directly from the browser.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

function forgetme_info()
{
	return array(
		'name'			=> 'Forget Me',
		'description'	=> $lang->hello_desc,
		'website'		=> 'https://lenders-it.nl',
		'author'		=> 'Burnacid (S.Lenders)',
		'authorsite'	=> 'https://lenders-it.nl',
		'version'		=> '0.1',
		'compatibility'	=> '18*',
		'codename'		=> 'forgetme'
	);
}


?>