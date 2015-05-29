<?php

if(!defined("WP_UNINSTALL_PLUGIN")) {
    exit();
}

delete_option("cdnify_api_key");
delete_option("cdnify_resource");

?>