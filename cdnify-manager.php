<?php

/**
 * Plugin Name: CDNify Manager
 * Plugin URI: https://www.paulandsam.co/cdnify-manager/
 * Description: CDNify.com for WordPress - Power your website with a Content Delivery Network (CDN). View and manage your resources, create new resources within WordPress and choose which resource your website should use.
 * Version: 1.2
 * Author: Paul Gillespie
 * Author URI: https://www.paulandsam.co/
 * License: GPL v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
 /*
  CDNify Manager is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 2 of the License, or
  any later version.
 
  CDNify Manager is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
 
  You should have received a copy of the GNU General Public License
  along with CDNify Manager. If not, see http://www.gnu.org/copyleft/gpl.html.
 */
 
include_once("cdnify-api.php");
 
class CDNifyManager {

    function admin_page() {
        add_submenu_page("tools.php", "CDNify", "CDNify", "administrator", "cdnify-manager", array("CDNifyManager", "menu_page"));
    }
    
    function enqueue_style() {
        wp_enqueue_style("cdnify-manager", plugins_url("css/cdnify-manager.css", __FILE__));
    }
    
    function enqueue_javascript() {
        wp_register_script("highcharts", plugins_url("js/highcharts.js", __FILE__), array(), "3.0.10");
        wp_register_script("cdnify-manager", plugins_url("js/cdnify-manager.js", __FILE__), array("jquery"), "1.0");
        
        wp_enqueue_script("highcharts");
        wp_enqueue_script("cdnify-manager");
    }
    
    function display_success($message="") {
    ?>
    
        <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
            <p><strong><?php print($message); ?></strong></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
    
    <?php
    }
    
    function display_error($message="") {
    ?>
    
        <div id="setting-error-invalid_home" class="error settings-error notice is-dismissible"> 
            <p><strong><?php print($message); ?></strong></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
    
    <?php
    }
    
    function menu_page() {
        $CDNify = new CDNify_API();
        $CDNify->setAPIKey(get_option("cdnify_api_key"));
                    
        $Errors          = array();
        $CreatedResource = false;
        $UpdatedResource = false;
        $DeletedResource = false;
        $APIError        = false;
        $ErrorMsg        = "Please check the errors.";
        
        if(isset($_POST["use_cdnify_resource_nonce"]) && wp_verify_nonce($_POST["use_cdnify_resource_nonce"], "use_cdnify_resource")) {
            $UseResource = sanitize_text_field($_POST["use_resource"]);
            
            update_option("cdnify_resource", $UseResource);
            
            if($_POST["delete_resource"] != null) {
                foreach($_POST["delete_resource"] as $ResourceID) {
                    $CDNify->deleteResource($ResourceID);
                    
                    $DeletedResource = true;
                    
                    $Resources     = $CDNify->getResources();
                    $ClearResource = true;
                    
                    foreach($Resources->resources as $Resource) {
                        if($Resource->hostname == get_option("cdnify_resource")) {
                            $ClearResource = false;
                        }
                    }
                    
                    if($ClearResource) {
                        update_option("cdnify_resource", "");
                    }
                }
            }
            
            $UpdatedResource = true;
        }
        
        if(isset($_POST["create_cdnify_resource_nonce"]) && wp_verify_nonce($_POST["create_cdnify_resource_nonce"], "create_cdnify_resource")) {
            $NewResourceOrigin = sanitize_text_field($_POST["cdnify_resource_origin"]);
            $NewResourceName   = sanitize_text_field($_POST["cdnify_resource_name"]);
            
            if(strlen($NewResourceOrigin) == 0) {
                $Errors["cdnify_resource_origin"] = "This field is required.";
            }
            else if(!preg_match("/\./", $NewResourceOrigin)) {
                $Errors["cdnify_resource_origin"] = "Please enter a valid URL.";
            }
            
            if(strlen($NewResourceName) == 0) {
                $Errors["cdnify_resource_name"] = "This field is required.";
            }
            else if(strlen($NewResourceName) < 5) {
                $Errors["cdnify_resource_name"] = "Please enter at least 5 characters.";
            }
            else if(preg_match("/[^0-9a-z]/", $NewResourceName)) {
                $Errors["cdnify_resource_name"] = "Alias can be lowercase only.";
            }
            
            if(count($Errors) == 0) {
                $ReturnData = $CDNify->createResource(array("origin" => $NewResourceOrigin, "alias" => $NewResourceName));
                
                if(isset($ReturnData->errors)) {
                    $APIError = true;
                    
                    $ErrorMsg = $ReturnData->errors[0]->message . ".";
                }
                else {
                    $CreatedResource = true;
                }
            }
        }
        ?>
    
        <div class="wrap">
            <h2>CDNify</h2>
            
            <?php
            if($UpdatedResource) {
                CDNifyManager::display_success("Settings saved.");
                
                $_POST = array();
            }
            
            if($DeletedResource) {
                CDNifyManager::display_success("Resource(s) delete.");
                
                $_POST = array();
            }
            
            if($CreatedResource) {
                CDNifyManager::display_success('Your resource "' . $NewResourceName . '" has been created');
                
                $_POST = array();
            }
            else if((count($Errors) > 0) || ($APIError)) {
                CDNifyManager::display_error($ErrorMsg);
            }
            ?>
            
            <?php
            if(get_option("cdnify_api_key") == "") {
                CDNifyManager::update_cdnify_api_key(true);
            }
            
            if(get_option("cdnify_api_key") != "") {
                $ActiveTab = "cdnify-resources";
                
                if(isset($_GET["tab"])) {
                    $ActiveTab = $_GET["tab"];
                }
                ?>
            
                <h2 class="nav-tab-wrapper">
		            <a href="<?php bloginfo("url"); ?>/wp-admin/tools.php?page=cdnify-manager&tab=cdnify-resources" class="nav-tab <?php if($ActiveTab == "cdnify-resources") { print('nav-tab-active'); } ?>">Resources</a>
					<a href="<?php bloginfo("url"); ?>/wp-admin/tools.php?page=cdnify-manager&tab=cdnify-settings" class="nav-tab <?php if($ActiveTab == "cdnify-settings") { print('nav-tab-active'); } ?>">Settings</a>
			    </h2>
			    
			    <div class="cdnify-container <?php if($ActiveTab == "cdnify-resources") { print('cdnify-container-active'); } ?>" id="cdnify-resources">
			        <h3>Your Resources</h3>
			        
			        <?php
                    $Resources = $CDNify->getResources();
                    
                    if(count($Resources->resources) > 0) {
                    ?>
                    
                        <form method="post" action="">
                            <?php wp_nonce_field("use_cdnify_resource", "use_cdnify_resource_nonce"); ?>
                            <table class="wp-list-table widefat fixed striped posts">
    	                        <thead>
    	                            <tr>
    		                            <th scope="col" id="title" class="manage-column column-title sortable desc" style=""><a><span>Alias</span></a></th>
    		                            <th scope="col" id="title" class="manage-column column-title sortable desc" style=""><a><span>Origin</span></a></th>
    		                            <th scope="col" id="title" class="manage-column column-title sortable desc" style=""><a><span>Hostname</span></a></th>
    		                            <th scope="col" id="title" class="manage-column column-title sortable desc" style=""><a><span>Created</span></a></th>
    		                            <th scope="col" id="title" class="manage-column column-title sortable desc cdnify-manage-column" style=""><a><span>Use?</span></a></th>
    		                            <th scope="col" id="title" class="manage-column column-title sortable desc cdnify-manage-column" style=""><a><span>Delete?</span></a></th>
                                    </tr>
    	                        </thead>
    	                        <tbody id="the-list">
                        
                                    <?php
                                    foreach($Resources->resources as $Resource) {
                                    ?>
                                    
        				                <tr id="post-1" class="iedit author-self level-0 post-1 type-post status-publish format-standard hentry category-uncategorised">
                                            <td class="post-title page-title column-title"><strong><?php print($Resource->alias); ?></strong></td>
                                            <td class="post-title page-title column-title"><?php print($Resource->origin); ?></td>
                                            <td class="post-title page-title column-title"><?php print($Resource->hostname); ?></td>
                                            <td class="post-title page-title column-title"><?php print(date("d/m/Y", strtotime($Resource->created_at))); ?></td>
                                            <td class="post-title page-title column-title"><input <?php if(get_option("cdnify_resource") == $Resource->hostname) { print('checked="checked"'); } ?> type="radio" name="use_resource" value="<?php print($Resource->hostname); ?>"></td>
                                            <td class="post-title page-title column-title"><input type="checkbox" name="delete_resource[]" value="<?php print($Resource->id); ?>" /></td>
                    					</tr>
                                
                                    <?php
                                    }
                                    ?>
                                
                                </tbody>
                                
                            </table>
                            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings"></p>
                        </form>
                            
                    <?php        
                    }
                    else {
                    ?>
                    
                        <p>You don't appear to have any resources. Use the form below to create a new one.</p>
                    
                    <?php
                    }
                    ?>
                    
                    <div class="cdnify-section">
                        <h3>Create Resource</h3>
                        
                        <?php
                        $ResourceOrigin = site_url();
                        $ResourceName   = "";
                        
                        if(isset($_POST["cdnify_resource_origin"])) {
                            $ResourceOrigin = sanitize_text_field($_POST["cdnify_resource_origin"]);
                        }
                        
                        if(isset($_POST["cdnify_resource_name"])) {
                            $ResourceName = sanitize_text_field($_POST["cdnify_resource_name"]);
                        }
                        ?>
                        
                        <form method="post" action="">
                            <?php wp_nonce_field("create_cdnify_resource", "create_cdnify_resource_nonce"); ?>
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row"><label for="cdnify_resource_origin">Resource Origin</label></th>
                                        <td>
                                            <input name="cdnify_resource_origin" type="text" id="cdnify_resource_origin" value="<?php print($ResourceOrigin); ?>" class="regular-text <?php if(isset($Errors["cdnify_resource_origin"])) { print("cdnify-form-error"); } ?>">
                                            <?php if(isset($Errors["cdnify_resource_origin"])) { ?>
                                                <p class="description cdnify-description-error"><?php print($Errors["cdnify_resource_origin"]); ?></p>
                                            <?php } ?>
                                            <p class="description">Which website should we mirror on the CDN?</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="cdnify_resource_name">Resource Name</label></th>
                                        <td>
                                            <input name="cdnify_resource_name" type="text" id="cdnify_resource_name" value="<?php print($ResourceName); ?>" class="regular-text <?php if(isset($Errors["cdnify_resource_name"])) { print("cdnify-form-error"); } ?>">
                                            <?php if(isset($Errors["cdnify_resource_name"])) { ?>
                                                <p class="description cdnify-description-error"><?php print($Errors["cdnify_resource_name"]); ?></p>
                                            <?php } ?>
                                            <p class="description">Something to identify this resource by (e.g. <strong>mysite</strong>.a.cdnify.io)</p>
                                        </td>
                                    </tr>
                            	</tbody>
                            </table>
                            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Create Resource"></p>
                        </form>
                    </div>
			    </div>
			    <div class="cdnify-container <?php if($ActiveTab == "cdnify-cache") { print('cdnify-container-active'); } ?>" id="cdnify-cache">
			        
			    </div>
			    <div class="cdnify-container <?php if($ActiveTab == "cdnify-settings") { print('cdnify-container-active'); } ?>" id="cdnify-settings">
			        <?php CDNifyManager::update_cdnify_api_key(); ?>
			    </div>
            
            <?php
            }
            ?>
            
        </div>
    
    <?php
    }
    
    function update_cdnify_api_key($inital_setup=false) {
        global $GLOBALS;
        
        $Updated = false;
        
        if(isset($_POST["cdnify_manager_nonce"]) && wp_verify_nonce($_POST["cdnify_manager_nonce"], "update_cdnify_manager_nonce")) {
            $GLOBALS["wp_object_cache"]->delete("cdnify_api_key", "options");
            
            $APIKey = sanitize_text_field($_POST["cdnify_api_key"]);
            
            update_option("cdnify_api_key", $APIKey);
            
            $Updated = true;
        }
        
        if((!$Updated) || (!$inital_setup)) {
        ?>
            
            <p>To use CDNify Manager, you must supply your API key. You can retrieve your API key from this page: <a href="https://cdnify.com/learn/api" target="_blank">https://cdnify.com/learn/api</a></p>
            <p>Please copy and paste your API key into the box below and click on "Save Changes".</p>
            <form method="post" action="">
                <?php wp_nonce_field("update_cdnify_manager_nonce", "cdnify_manager_nonce"); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="cdnify_api_key">API Key</label></th>
                            <td><input name="cdnify_api_key" type="text" id="cdnify_api_key" value="<?php print(get_option("cdnify_api_key")); ?>" class="regular-text"></td>
                        </tr>
                	</tbody>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
            </form>
        
        <?php
        }
    }
    
    static function use_cdnify() {
        if(get_option("cdnify_resource") != "") {
            if(get_option("cdnify_api_key") != "") {
                add_filter("script_loader_src", array("CDNifyManager", "change_urls"));
                add_filter("style_loader_src", array("CDNifyManager", "change_urls"));
                add_filter("wp_get_attachment_thumb_url", array("CDNifyManager", "change_urls"));
                add_filter("wp_get_attachment_url", array("CDNifyManager", "change_urls"));
                add_filter("pre_link_image", array("CDNifyManager", "change_urls"));
                add_filter("the_content", array("CDNifyManager", "change_content"));
                
                $options = wp_load_alloptions();
                
                foreach($options as $name => $value) {
                    if((preg_match("/\.png$/", $value)) || (preg_match("/\.jpg$/", $value))) {
                        add_filter("option_" . $name, array("CDNifyManager", "change_urls"));
                    }
                }
            }
        }
    }
    
    static function get_protocol() {
        $protocol = "http";
        
        if(preg_match("/^https/", site_url())) {
            $protocol = "https";
        }
        
        return($protocol);
    }
    
    static function change_urls($url, $id=null) {
        if(!is_admin()) {
            $url = str_replace(site_url(), CDNifyManager::get_protocol() . "://" . get_option("cdnify_resource"), $url);
        }
        
        return($url);
    }
    
    static function change_content($content) {
        if(get_post_status(get_the_ID()) == "publish") {
            if(preg_match_all('/img.*?src="(.*?)"/', $content, $matches)) {
                foreach($matches[1] as $image) {
                    $content = str_replace($image, CDNifyManager::change_urls($image), $content);
                }
            }
        }
        
        return($content);
    }

    function dashboard_widget_graph() {
        wp_add_dashboard_widget(
                                "cdnify_dashboard_graph",
                                "CDNify",
                                array("CDNifyManager", "dashboard_widget_graph_contents"));
    }

    function dashboard_widget_graph_contents() {
        // https://cdnify.com/api/v1/stats/{resource_id}/bandwidth?datefrom={YYYY-MM-DD}&dateto={YYYY-MM-DD}
        
        if(get_option("cdnify_api_key") != "") {
            if(get_option("cdnify_resource") != "") {
                $CDNify = new CDNify_API();
                $CDNify->setAPIKey(get_option("cdnify_api_key"));
                
                $Resources = $CDNify->getResources();
                    
                if(count($Resources->resources) > 0) {
                    $ResourceID = "";
                    
                    foreach($Resources->resources as $Resource) {
                        if(get_option("cdnify_resource") == $Resource->hostname) {
                            $ResourceID = $Resource->id;
                        }
                    }
                    
                    if($ResourceID != "") {
                        
                        $CurrentBandwidth = $CDNify->getResourceBandwidth($ResourceID, "2015-06-01", "2015-06-30");
                        $NumberOfDays     = cal_days_in_month(CAL_GREGORIAN, date("n"), date("Y"));
                        $Sep              = ", ";
                        $Data             = "[";
                        $DataList         = array();
                        
                        foreach($CurrentBandwidth->overall_usage[0] as $Day) {
                            $DataList[date("d", strtotime($Day->timestamp))] = $Day;
                        }
                        
                        for($Loop=1; $Loop<=$NumberOfDays; $Loop++) {
                            if($Loop == $NumberOfDays) {
                                $Sep = "";
                            }
                
                            if(array_key_exists($Loop, $DataList)) {
                                $Data .= $DataList[$Loop]->hits . $Sep;
                            }
                            else {
                                $Data .= "0" . $Sep;
                            }
                        }
                        
                        $Data .= "]";
            
                        print('<div id="cdnify-dashboard-graph" data-input="' . $Data . '" data-input-last="' . $Data . '"></div>');
                    }
                    else {
                        print("Your selected resource does not exist.");
                    }
                }
                else {
                    print("You don't have any resources.");
                }
            }
            else {
                print("Please select a resource for your website to use.");
            }
        }
        else {
            print("Please update your API key to view your resource bandwidth.");
        }
    }
    
}

add_action("init", array("CDNifyManager", "use_cdnify"));
add_action("admin_menu", array("CDNifyManager", "admin_page"));
add_action("admin_init", array("CDNifyManager", "enqueue_style"));
//add_action("admin_enqueue_scripts", array("CDNifyManager", "enqueue_javascript"));
//add_action("wp_dashboard_setup", array("CDNifyManager", "dashboard_widget_graph"));

?>