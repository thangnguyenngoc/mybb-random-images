<?php

/**
 * @author Chii (https://github.com/thangnguyenngoc/mybb-random-images) 
 * @copyright 2014
 */

//Add hook for index
$plugins->add_hook('index_start', 'rpi');

//info for index
function rpi_info()
{
	return array(
		'name'			=> 'Random Index Images',
		'description'	=> 'Randomly show images in Index',
		'website'		=> 'https://github.com/thangnguyenngoc/mybb-random-images',
		'author'		=> 'Chii, https://github.com/thangnguyenngoc/mybb-random-images',
		'authorsite'	=> 'https://github.com/thangnguyenngoc/mybb-random-images',
		'version'		=> '1.0',
		'compatibility' => '16*,14*',
        'guid'          => '27f9805fe0d84ee281de9e210d407d27'
	);
}

function rpi_activate()
{
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';
    global $db,$mybb;
    $query = $db->simple_select("settinggroups","COUNT(*) as rows");
	$rows = $db->fetch_field($query,"rows");
    $rpi_group = array('name' => 'rpi','title' => 'Random Index Images','description' => 'Settings for Random Index Images Plugin','disporder' =>$rows + 1,'isdefault' => '0',);
    $db->insert_query('settinggroups',$rpi_group);
	$gid = $db->insert_id();
    $rpi_setting_1 = array('name' => 'showrpi','title' =>'On/Off','description' =>'Display Random Index images in Index?','optionscode' => 'onoff','value' => '1','disporder' => 1,'gid' => intval($gid),);
    $rpi_setting_2 = array('name' => 'pofrpi','title' =>'Position ','description' =>'Where do you want to display Random Index Images?','optionscode' => 'select\nheader=Header\nfooter=Footer','value' => 'header','disporder' => 2,'gid' => intval($gid),);
    $rpi_setting_3 = array('name' => 'inbrpi','title' =>'Custom text or banner','description' =>'You can enter text or arbitrary code for displayed in below Random Index Images.','optionscode' => 'textarea','value' => '','disporder' => 3,'gid' => intval($gid),);
    $rpi_setting_4 = array('name' => 'limitrpi','title' =>'Number of Images','description' =>'How many images would be displayed?','optionscode' => 'text','value' => '5','disporder' => 4,'gid' => intval($gid),);
    $db->insert_query('settings',$rpi_setting_1);
    $db->insert_query('settings',$rpi_setting_2);
    $db->insert_query('settings',$rpi_setting_3);
    $db->insert_query('settings',$rpi_setting_4);
    rebuildsettings();
    $rpi_template = array(
		"title"		=> 'rpi',
		"template"	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="4" align="center"><strong>{$lang->rpi}</strong></td>
</tr>
<tr>
<td class="trow1" width="100%">
{$feed1_rpi}
</td>
</tr>
{$banner}
</table>
<div style="text-align: right; font-size: 10px;"> Index images by <a href="https://github.com/thangnguyenngoc/mybb-random-images" target="blank">Chii</a></div><br />'),
		"sid"		=> "-1",
		"version"	=> "1.0",
		"dateline"	=> "1407956400",
	);
	$db->insert_query("templates", $rpi_template);
    find_replace_templatesets("index", '#{\$boardstats}#', "{\$rpif}\n{\$boardstats}");
    find_replace_templatesets("index", '#{\$header}#', "{\$header}\n{\$rpih}");
}

//Deactive rpi (very good because no change need)
function rpi_deactivate()
{
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';
    global $db;
    $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title='rpi'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN('showrpi', 'rpi')");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN('pofrpi', 'rpi')");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN('inbrpi', 'rpi')");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN('limitrpi', 'rpi')");
    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='rpi'");
    rebuildsettings();
    find_replace_templatesets("index", '#'.preg_quote('{$rpif}').'#', '',0);
    find_replace_templatesets("index", '#'.preg_quote('{$rpih}').'#', '',0);
    
}

//Function of rpi really easy (As easy as hot cake)
function rpi()
{
    require_once MYBB_ROOT.'/firephpcore/FirePHP.class.php';
    require_once MYBB_ROOT.'/firephpcore/fb.php';
    global $db, $theme, $mybb, $templates, $lang, $rpif, $rpih,$rpi;
    ob_start();
    $firephp = FirePHP::getInstance(true);
    $lang->load("rpi");
    if($mybb->settings['showrpi'] != 0)
    {
        $count = 0;
        $query = $db->query("SELECT p.pid,p.subject,p.message FROM ".TABLE_PREFIX."posts p WHERE p.message LIKE \"%[img]%[/img]%\" ORDER BY RAND() LIMIT 0,100");
        $feed1_rpi = '<ul style="list-style-type:none; margin:0px; padding:0px;">';
        while($post = $db->fetch_array($query))
        {
            preg_match("/\\[img\\](.*?)\\[\\/img\\]/m", $post['message'], $matches);
            if (is_array($matches) && count($matches) > 1)
            {
                $image = '<img src="'.$matches[1].'" width="100px" height="100px" style="display:block;">';
                $link = "<a href=\"{$mybb->settings['bburl']}/showthread.php?pid=".$post['pid']."\" title=\"{$post['subject']}\" style=\"display:block;\">{$image}</a>";
                $feed1_rpi .= "<li style=\"display:inline-block;\">{$link}</li>";
                $count++;
                if ($count>$mybb->settings['limitrpi'])
                {
                    break;
                }
            }
        }
        $feed1_rpi .= '</ul>';
        
        //check banner
        if($mybb->settings['inbrpi'] != '')
        {
            $banner = '<tr><td colspan="4" class="trow1" align="center">'.$mybb->settings['inbrpi'].'</td></tr>';
        }
        //get template
        if($mybb->settings['pofrpi'] == 'header')
        {
            eval("\$rpih = \"".$templates->get("rpi")."\";");
            $rpif = "";
        }
        else
        {     
            eval("\$rpif = \"".$templates->get("rpi")."\";");
            $rpih = "";
        }
    }
}
?>