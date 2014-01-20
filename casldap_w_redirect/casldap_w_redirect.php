<?php
/*
Plugin Name: CAS/LDAP with Redirect
Version: 1.1
Plugin URI: http://michaelseiler.net/cas_and_ldap_with_redirect_wp_plugin
Description: A plugin that authenticates users against a CAS server, retrieves user data from an LDAP, and redirects to any page you wish after user has authenticated.
Author: Mike Seiler
Author URI: http://michaelseiler.net/
*******
    Copyright (C) 2013  Mike Seiler 
    http://www.linkedin.com/in/cmichaelseiler/
    http://www.github.com/cmikeseiler

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details: <http://www.gnu.org/licenses/>
*/
// SET IT ALL UP
$cas_host = (string)get_option('CASLDAP_CAS_SERVER');
$cas_port = (int)get_option('CASLDAP_CAS_PORT');
$cas_path = get_option('CASLDAP_CAS_PATH');
$cas_file = __DIR__ ."/CAS.php";
// CAS FILE IS PACKAGED WITH THIS PLUGIN, SO IT'S SAFE TO INCLUDE IT WITHOUT CHECKING FOR EXISTENCE
require_once($cas_file);
// IF CAS_HOST IS FALSE, THEN IT HAS NOT BEEN SET, I.E. NO SETTINGS CONFIGURED YET
if( (!$cas_host == false))  
{
	phpCAS::client(get_option('CASLDAP_CAS_VERSION'), $cas_host, $cas_port, $cas_path);
	// FILTERS TO HOOK INTO WORDPRESS FUNCTIONS
	if(($_GET) && ($_GET['auth'] == 'NOCAS') ) {
		// do nothing, let wordpress do the work
		// and present the form.  just capturing a use case
	} elseif( ($_POST) && isset($_POST['wp-submit'])) {
		// also do nothing, let wordpress do the work
		// here just because we need to capture the scenario of logging in without CAS/LDAP
	} else {
		add_filter('wp_authenticate', 'doCASLogin',10,2);
		add_filter('wp_logout','doCASLogout',10,0);
	}
}

# CHECK TO SEE IF WE ARE GETTING AN UPDATE FROM OUR ADMIN SETTINGS PAGE
if( ($_POST) && ($_POST['updateCASLDAP'] == "YES") )
{
	# UPDATE WP_OPTIONS TABLE
	update_option('CASLDAP_CAS_VERSION',$_POST['casldap_cas_version']);
	update_option('CASLDAP_CAS_SERVER',$_POST['casldap_cas_server']);
	update_option('CASLDAP_CAS_PORT',$_POST['casldap_cas_port']);
	update_option('CASLDAP_CAS_PATH',$_POST['casldap_cas_path']);
	update_option('CASLDAP_LDAP_HOST',$_POST['casldap_ldap_host']);
	update_option('CASLDAP_LDAP_PORT',$_POST['casldap_ldap_port']);
	update_option('CASLDAP_LDAP_BASEDN',$_POST['casldap_ldap_basedn']);
	update_option('CASLDAP_LDAP_BIND_USER',$_POST['casldap_ldap_bind_user']);
	update_option('CASLDAP_LDAP_BIND_PASS',$_POST['casldap_ldap_bind_pass']);
	update_option('CASLDAP_LDAP_USER_LOGIN',$_POST['casldap_ldap_user_login']);
	update_option('CASLDAP_LDAP_USER_EMAIL',$_POST['casldap_ldap_user_email']);
	update_option('CASLDAP_LDAP_FIRST_NAME',$_POST['casldap_ldap_first_name']);
	update_option('CASLDAP_LDAP_LAST_NAME',$_POST['casldap_ldap_last_name']);
	update_option('CASLDAP_LDAP_NICKNAME',$_POST['casldap_ldap_nickname']);
	update_option('CASLDAP_LDAP_DISPLAY_NAME',$_POST['casldap_ldap_display_name']);
	update_option('CASLDAP_USER_REDIRECT',$_POST['casldap_user_redirect']);
	update_option('CASLDAP_ADMIN_REDIRECT',$_POST['casldap_admin_redirect']);
		
	# NOW CALL THE ADMIN SETTINGS MENU AGAIN
	add_action('admin_menu', 'casldap_admin_menu');
}
else
{
	# CALL THE ADMIN SETTINGS MENU
	add_action('admin_menu', 'casldap_admin_menu');
}
# THE FUNCTION THAT CREATES AN OPTION PAGE IN THE MENU BAR OF THE DASHBOARD
function casldap_admin_menu() {
	add_options_page("CAS/LDAP Settings","CAS/LDAP Settings","activate_plugins",__FILE__,"casldap_options_page");
}
# THE FUNCTION THAT CREATES THE OPTIONS PAGE
function casldap_options_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	else
	{
		$options_list = casldap_options();
?>
<div class="wrap">
<h2><?php _e('Settings for CAS/LDAP with Redirect','casldap');?></h2>
<?php if($_POST['updateCASLDAP'] == "YES") { ?>
<div id='setting-error-settings_updated' class='updated settings-error'><p><strong><?php echo _e('Options Saved','casldap');?></strong></p></div>
<?php } ?>
<h3>Important Note Concerning Setup:</h3>
<p>Once you set these settings <em>and log out</em> Word Press will force you to login via CAS.  If you are currently logged in as the Admin, you will not be able to login as the Admin through your CAS server unless your current profile data matches what is in your LDAP.  The safest option is to use another browser to test the settings, and stay logged in as the Admin in this browser to fix any errors.</p>
<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<input type="hidden" name="updateCASLDAP" value="YES">
<table border="0" width="50%" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="2"><h3>CAS Settings</h3></td>
    </tr>
    <tr>
        <td align="right"><strong><?php _e('CAS Version:');?></strong></td>
        <td><select name="casldap_cas_version" id="casldap_cas_version">
                    <option value="2.0" <?php echo ($options_list['cas_version'] == '2.0')? 'selected':''; ?>>2.0</option>
                    <option value="1.0" <?php echo ($options_list['cas_version'] == '1.0')? 'selected':''; ?>>1.0</option>
                </select>
		</td>
    </tr>
    <tr>
        <td align="right"><strong>CAS Server:</strong></td>
        <td><input type="text" name="casldap_cas_server" id="casldap_cas_server" size="30" value="<?php echo $options_list['cas_server'];?>"></td>
    </tr>
    <tr>
        <td align="right"><strong>CAS Port:</strong></td>
        <td><input type="text" size="20" name="casldap_cas_port" id="casldap_cas_port" value="<?php echo $options_list['cas_port'];?>"></td>
    </tr>
    <tr>
        <td align="right"><strong>CAS Server Path:</strong></td>
        <td><input type="text" size="20" name="casldap_cas_path" id="casldap_cas_path" value="<?php echo $options_list['cas_path'];?>"></td>
    </tr>
    <tr>
        <td colspan="2"><h3>LDAP Settings</h3></td>
    </tr>
    <tr>
        <td align="right"><strong>LDAP Server:</strong></td>
        <td><input type="text" size="30" name="casldap_ldap_host" id="casldap_ldap_host" value="<?php echo $options_list['ldap_host'];?>"></td>
    </tr>
    <tr>
        <td align="right"><strong>LDAP Port:</strong></td>
        <td><input type="text" size="20" name="casldap_ldap_port" id="casldap_ldap_port" value="<?php echo $options_list['ldap_port'];?>"></td>
    </tr>
    <tr>
        <td align="right"><strong>LDAP Base DN:</strong></td>
        <td><input type="text" size="45" name="casldap_ldap_basedn" id="casldap_ldap_basedn" value="<?php echo $options_list['ldap_basedn'];?>"></td>
    </tr>
    <tr>
        <td align="right"><strong>LDAP Bind User:</strong></td>
        <td><input type="text" size="25" name="casldap_ldap_bind_user" id="casldap_ldap_bind_user" value="<?php echo $options_list['ldap_bind_user'];?>"></td>
    </tr>
	<tr>
        <td align="right"><strong>LDAP Bind Password</strong></td>
        <td><input type="password" size="25" name="casldap_ldap_bind_pass" id="casldap_ldap_bind_pass" value="<?php echo $options_list['ldap_bind_pass'];?>"></td>
    </tr>
	<tr>
        <td colspan="2"><h3>LDAP Data Mapping (for new user creation)</h3></td>
    </tr>
	<tr>
        <td align="right"><strong>LDAP Username field</strong></td>
        <td><input type="text" size="25" name="casldap_ldap_user_login" id="casldap_ldap_user_login" value="<?php if(isset($options_list['ldap_user_login'])) { echo $options_list['ldap_user_login'];} else { echo "uid"; } ?>"></td>
    </tr>
	<tr>
        <td align="right"><strong>Email Field</strong></td>
        <td><input type="text" size="25" name="casldap_ldap_user_email" id="casldap_ldap_user_email" value="<?php if(isset($options_list['ldap_user_email'])) { echo $options_list['ldap_user_email'];} else { echo "mail";} ?>"></td>
    </tr>
	<tr>
        <td align="right"><strong>First Name</strong></td>
        <td><input type="text" size="25" name="casldap_ldap_first_name" id="casldap_ldap_first_name" value="<?php if(isset($options_list['ldap_first_name'])) { echo $options_list['ldap_first_name'];} else { echo "givenName";} ?>"></td>
    </tr>
	<tr>
        <td align="right"><strong>Last Name</strong></td>
        <td><input type="text" size="25" name="casldap_ldap_last_name" id="casldap_ldap_last_name" value="<?php if(isset($options_list['ldap_last_name'])) { echo $options_list['ldap_last_name'];} else { echo "sn";} ?>"></td>
    </tr>
	<tr>
        <td align="right"><strong>Nickname</strong></td>
        <td><input type="text" size="25" name="casldap_ldap_nickname" id="casldap_ldap_nickname" value="<?php if(isset($options_list['ldap_nickname'])) { echo $options_list['ldap_nickname'];} else { echo "";} ?>"></td>
    </tr>
	<tr>
        <td align="right"><strong>Display Name</strong></td>
        <td><input type="text" size="25" name="casldap_ldap_display_name" id="casldap_ldap_display_name" value="<?php if(isset($options_list['ldap_display_name'])) { echo $options_list['ldap_display_name'];} else { echo "displayName";} ?>"></td>
    </tr>
    <tr>
        <td colspan="2"><h3>Redirect Options</h3></td>
    </tr>
    <tr>
        <td colspan="2">Insert the page URL here without leading "/" <br/>(e.g. <code>index.php</code>, <code>wp-admin/index.php</code>, <code>2013/03/18/sample-post/</code>, <code>?p=123)</code></td>
    </tr>
    <tr>
        <td align="right"><strong>User Redirect:</strong></td>
        <td><input type="text" size="30" name="casldap_user_redirect" id="casldap_user_redirect" value="<?php echo $options_list['user_redirect'];?>"></td>
    </tr>
	<tr>
        <td align="right"><strong>Admin Redirect:</strong></td>
        <td><input type="text" size="30" name="casldap_admin_redirect" id="casldap_admin_redirect" value="<?php echo isset($options_list['admin_redirect'])? $options_list['admin_redirect']:'e.g. /wp-admin/index.php';?>"></td>
    </tr>
	<tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="<?php _e('Update Options','casldap');?>"></td>
    </tr>
</table>
</form>
</div>
<?php
	}
}
# THE ACTUAL FUNCTION THAT SENDS PEOPLE TO THE CAS SERVER AND AUTHENTICATES
function doCASLogin() 
{
	global $wpdb;
	// SET IT ALL UP
	$user_redirect = get_option('CASLDAP_USER_REDIRECT');
	$admin_redirect = get_option('CASLDAP_ADMIN_REDIRECT');
	# COMMENT THIS OUT IN PRODUCTION - BETTER TO VALIDATE THE SERVER
	# THIS WILL REQUIRE PROPER SETUP OF SSL
	phpCAS::setNoCasServerValidation();
	
	// CHECK IF THEY WERE HERE BEFORE
	if(phpCAS::isAuthenticated())
	{
		// GET THE USERNAME
		$username = phpCAS::getUser();
		$uname_result = get_user_by('login',$username);
		if( $uname_result == true )
		{
			if( $uname_result->user_login != '' )
			{
				// LOG THEM IN
				// THE FOLLOWING FUNCTION NEEDS ID, SO WE USE THE OBJECT ABOVE
				wp_set_auth_cookie($uname_result->id, true);
				if( isset($uname_result->caps['administrator']) && ($uname_result->caps['administrator'] == 1) )
				{
					wp_redirect($admin_redirect);
				}
				else
				{
					wp_redirect($user_redirect);
				}
			}
		}
		else
		{
			// CREATE THE NEW USER
			$user_details = getLDAPInfo($username);
			if($user_details)
			{
				$ldap_options = casldap_options();
				// LDAP SEARCH SEEMS TO RETURN EVERYTHING IN LOWERCASE, SO...
				$ldap_options = array_map('strtolower',$ldap_options);
				$rev_ldap_opts = array_flip($ldap_options);
				// SET UP OUR ARRAY FOR INSERTING INTO WP DATABASE
				$new_user_data = array();
				foreach($user_details[0] as $key => $val)
				{
					// LDAP RESULT HAS BOTH ASSOCIATIVE AND NUMERICAL ARRAY INDICES WE WANT THE ASSOCIATIVE ONLY
					if(!is_int($key)) 
					{
						// $val IS AN ARRAY FROM WHICH WE WANT THE SECOND ELEMENT
						$val_val = $val[0];
						// LDAP SENDS BACK A COUNT AND A DN ENTRY, NEITHER OF WHICH WE NEED HERE
						if( ($key != 'dn') && ($key != 'count') )
						{
							$wp_db_hook = str_replace("ldap_","",$rev_ldap_opts[$key]);
							$new_user_data[$wp_db_hook] = $val_val;
						}
					}
				}
				if(!isset($auth)) {
					$new_user_data["user_pass"] = substr( hash("whirlpool", time()), 0, 8);
					wp_insert_user($new_user_data);
					$new_user = get_user_by('login',$username);
				}
		        wp_set_auth_cookie($new_user->id, true);
				wp_redirect($user_redirect);
			}
			else
			{
				echo "Error.  Failure to retrieve data for this username: $username<br>";	
			}
		}
	}
	else // FORCE THE USER TO AUTHENTICATE
	{
		phpCAS::forceAuthentication();
	}
}
# THE FUNCTION THAT LOGS PEOPLE OUT OF BOTH WORDPRESS AND THE CAS SERVER
function doCASLogout() {
	phpCAS::logout( array( 'url' => get_settings( 'siteurl' )));
}
# THE FUNCTION THAT RETRIEVES THE USER DETAILS FROM THE LDAP SERVER BASED ON USERNAME
function getLDAPInfo($username) 
{
	$ldap_host = get_option('CASLDAP_LDAP_HOST');
	$ldap_port = (int) get_option('CASLDAP_LDAP_PORT');
	$ldap_basedn = (string) get_option('CASLDAP_LDAP_BASEDN');
	// PRIMARY SEARCH FILTER IS USUALLY USERNAME
	$ldap_attributes = array();
	// WE ALSO WANT TO PUSH THE PRIMARY FILTER SO WE CAN VERIFY LATER
	$ldap_user_login = get_option('CASLDAP_LDAP_USER_LOGIN');
	array_push($ldap_attributes,$ldap_user_login);
	$ldap_search_filter = "($ldap_user_login=$username)";
	// "DATA MAPPING" OPTIONS FROM THE ADMIN SETTINGS PAGE - RETRIEVE AND PUT INTO AN ARRAY AS OUR ATTRIBUTE RETRIEVAL LIST
	array_push($ldap_attributes,get_option('CASLDAP_LDAP_USER_EMAIL'));
	array_push($ldap_attributes,get_option('CASLDAP_LDAP_FIRST_NAME'));
	array_push($ldap_attributes,get_option('CASLDAP_LDAP_LAST_NAME'));
	array_push($ldap_attributes,get_option('CASLDAP_LDAP_NICKNAME'));
	array_push($ldap_attributes,get_option('CASLDAP_LDAP_DISPLAY_NAME'));
	// CONNECT TO THE LDAP SERVER
	$ldapconx = ldap_connect($ldap_host, $ldap_port) or die("Could not connect to LDAP server.");
	
	if($ldapconx)
	{
		$ldap_bind = ldap_bind($ldapconx, get_option('CASLDAP_LDAP_BIND_USER'), get_option('CASLDAP_LDAP_BIND_PASS')) or die ("Failed to bind to the LDAP server.");

		if($ldap_bind)
		{
			$ldap_search = ldap_search($ldapconx, "$ldap_basedn", "$ldap_search_filter", $ldap_attributes);
			$user_details = ldap_get_entries($ldapconx, $ldap_search);
			$ldap_user_login = strtolower($ldap_user_login);
			if(isset($user_details[0][$ldap_user_login][0]))
			{
				return $user_details;
			}
			else
			{
				return false;
			}
		}
		else
		{
			echo "There was a major failure!  <strong>Unable to bind to the ldap server.</strong>";
		}
	}
}
# THE FUNCTION THAT READS THE OPTIONS FROM THE DATABASE AND RETURNS THEM FOR USE
function casldap_options() 
{
	$options_list = array (
			'cas_version' => get_option('CASLDAP_CAS_VERSION'),
			//'cas_file' => get_option('CASLDAP_CAS_FILE'),
			'cas_server' => get_option('CASLDAP_CAS_SERVER'),
			'cas_port' => get_option('CASLDAP_CAS_PORT'),
			'cas_path' => get_option('CASLDAP_CAS_PATH'),
			'ldap_host' => get_option('CASLDAP_LDAP_HOST'),
			'ldap_port' => get_option('CASLDAP_LDAP_PORT'),
			'ldap_basedn' => get_option('CASLDAP_LDAP_BASEDN'),
			'ldap_bind_user' => get_option('CASLDAP_LDAP_BIND_USER'),
			'ldap_bind_pass' => get_option('CASLDAP_LDAP_BIND_PASS'),
			'ldap_user_login' => get_option('CASLDAP_LDAP_USER_LOGIN'),
			'ldap_user_email' => get_option('CASLDAP_LDAP_USER_EMAIL'),
			'ldap_first_name' => get_option('CASLDAP_LDAP_FIRST_NAME'),
			'ldap_last_name' => get_option('CASLDAP_LDAP_LAST_NAME'),
			'ldap_nickname' => get_option('CASLDAP_LDAP_NICKNAME'),
			'ldap_display_name' => get_option('CASLDAP_LDAP_DISPLAY_NAME'),
			'user_redirect' => get_option('CASLDAP_USER_REDIRECT'),
			'admin_redirect' => get_option('CASLDAP_ADMIN_REDIRECT')
		);
	foreach ($options_list as $key => $val) {
			$options_list[$key] = $val;	
	}
	return $options_list;
}
?>
