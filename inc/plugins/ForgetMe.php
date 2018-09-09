<?php

/**
 * @author stefan lenders
 * @copyright 2018
 */

// Make sure we can't access this file directly from the browser.
if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

function forgetme_info()
{
    global $lang;
    $lang->load('config_forgetme');

    return array(
        'name' => 'Forget Me',
        'description' => $lang->fm_desc,
        'website' => 'https://lenders-it.nl',
        'author' => 'Burnacid (S.Lenders)',
        'authorsite' => 'https://lenders-it.nl',
        'version' => '0.1',
        'compatibility' => '18*',
        'codename' => 'forgetme');
}

$plugins->add_hook('usercp_menu', 'forgetme_usercp_menu');
$plugins->add_hook('usercp_start', 'forgetme_usercp_start');

function forgetme_activate()
{
    global $db, $lang,$mybb;

    $templatearray = array(
        'usercp_nav_misc_removeaccount' => "<tbody style=\"{\$collapsed['usercpmisc_e']}\" id=\"usercpmisc_e\">
        <tr><td class=\"trow1 smalltext\"><a href=\"{\$fm_delete_account_url}\" class=\"usercp_nav_item usercp_nav_forgetme\">{\$lang->fm_delete_account}</a></td></tr>
        </tbody>",
        "usercp"=>
        "<html>
        <head>
        <title>{\$mybb->settings['bbname']} - {\$lang->fm_delete_account}</title>
        {\$headerinclude}
        </head>
        <body>
        {\$header}
        <form action=\"usercp.php\" method=\"post\">
        <input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
        <table width=\"100%\" border=\"0\" align=\"center\">
        <tr>
        {\$usercpnav}
        <td valign=\"top\">
        
        <table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" class=\"tborder\">
        <tbody><tr>
        	<td class=\"thead\" colspan=\"2\"><strong>{\$lang->fm_delete_account}</strong></td>
        </tr>
        <tr>
        <td class=\"trow1\" valign=\"top\" align=\"center\" width=\"1\">
        
        </td>
        </tr>
        </tbody></table>
        	
        </td>
        </tr>
        </table>
        </form>
        {\$footer}
        </body>
        </html>");

    $group = array('prefix' => $db->escape_string('forgetme'), 'title' => $db->
            escape_string('Forget Me'));

    // Update or create template group:
    $query = $db->simple_select('templategroups', 'prefix', "prefix='{$group['prefix']}'");

    if ($db->fetch_field($query, 'prefix')) {
        $db->update_query('templategroups', $group, "prefix='{$group['prefix']}'");
    } else {
        $db->insert_query('templategroups', $group);
    }

    // Query already existing templates.
    $query = $db->simple_select('templates', 'tid,title,template',
        "sid=-2 AND (title='{$group['prefix']}' OR title LIKE '{$group['prefix']}=_%' ESCAPE '=')");

    $templates = $duplicates = array();

    while ($row = $db->fetch_array($query)) {
        $title = $row['title'];
        $row['tid'] = (int)$row['tid'];

        if (isset($templates[$title])) {
            // PluginLibrary had a bug that caused duplicated templates.
            $duplicates[] = $row['tid'];
            $templates[$title]['template'] = false; // force update later
        } else {
            $templates[$title] = $row;
        }
    }

    // Delete duplicated master templates, if they exist.
    if ($duplicates) {
        $db->delete_query('templates', 'tid IN (' . implode(",", $duplicates) . ')');
    }

    // Update or create templates.
    foreach ($templatearray as $name => $code) {
        if (strlen($name)) {
            $name = "forgetme_{$name}";
        } else {
            $name = "forgetme";
        }

        $template = array(
            'title' => $db->escape_string($name),
            'template' => $db->escape_string($code),
            'version' => 1,
            'sid' => -2,
            'dateline' => TIME_NOW);

        // Update
        if (isset($templates[$name])) {
            if ($templates[$name]['template'] !== $code) {
                // Update version for custom templates if present
                $db->update_query('templates', array('version' => 0), "title='{$template['title']}'");

                // Update master template
                $db->update_query('templates', $template, "tid={$templates[$name]['tid']}");
            }
        }
        // Create
        else {
            $db->insert_query('templates', $template);
        }

        // Remove this template from the earlier queried list.
        unset($templates[$name]);
    }

    // Remove no longer used templates.
    foreach ($templates as $name => $row) {
        $db->delete_query('templates', "title='{$db->escape_string($name)}'");
    }

    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    find_replace_templatesets('usercp_nav', '#' . preg_quote('{$usercpmenu}') .
        '#', "{\$usercpmenu}\n{\$mybb->forgetme_usercp_nav_misc_removeaccount}");
        
    // Add stylesheet
    $tid = 1; // MyBB Master Style
    $name = "forgetme.css";
    $styles = "
    .usercp_nav_forgetme {
    	display: block;
    	padding: 1px 0 1px 23px;
    	background-image: url(../../../images/invalid.png);
    	background-repeat: no-repeat;
    }
    ";
    $attachedto = "usercp.php";
    $stylesheet = array(
        'name' => $name,
        'tid' => $tid,
        'attachedto' => $attachedto,
        'stylesheet' => $styles,
        'cachefile' => $name,
        'lastmodified' => TIME_NOW,
        );
    $dbstylesheet = array_map(array($db, 'escape_string'), $stylesheet);
    // Activate children, if present.
    $db->update_query('themestylesheets', array('attachedto' => $dbstylesheet['attachedto']), "name='{$dbstylesheet['name']}'");
    // Update or insert parent stylesheet.
    $query = $db->simple_select('themestylesheets', 'sid', "tid='{$tid}' AND cachefile='{$name}'");
    $sid = intval($db->fetch_field($query, 'sid'));
    if ($sid) {
        $db->update_query('themestylesheets', $dbstylesheet, "sid='$sid'");
    } else {
        $sid = $db->insert_query('themestylesheets', $dbstylesheet);
        $stylesheet['sid'] = intval($sid);
    }
    require_once MYBB_ROOT . $mybb->config['admin_dir'] . '/inc/functions_themes.php';
    if ($stylesheet) {
        cache_stylesheet($stylesheet['tid'], $stylesheet['cachefile'], $stylesheet['stylesheet']);
    }
    update_theme_stylesheet_list($tid, false, true); // includes all children
}

function forgetme_deactivate()
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    find_replace_templatesets('usercp_nav', '#' . preg_quote("\n{\$mybb->forgetme_usercp_nav_misc_removeaccount}") .
        '#', "");

}

function forgetme_usercp_menu()
{
    global $mybb, $templates, $usercp_nav_misc_removeaccount, $lang;

    $lang->load("forgetme");


    $fm_delete_account_url = "usercp.php?action=deleteaccount";

    eval("\$mybb->forgetme_usercp_nav_misc_removeaccount = \"" . $templates->get("forgetme_usercp_nav_misc_removeaccount") .
        "\";");

}

function forgetme_usercp_start()
{
    global $mybb, $lang, $templates, $headerinclude, $header, $usercpnav, $footer;

    if ($mybb->input['action'] == "deleteaccount") {
        add_breadcrumb($lang->fm_delete_account);

        eval("\$forgetme_delete_account = \"" . $templates->get("forgetme_usercp") . "\";");
        output_page($forgetme_delete_account);
    }
}


?>