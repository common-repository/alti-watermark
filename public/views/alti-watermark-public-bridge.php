<?php
/**
 * Independant files called outside the Wordpress loop. Used as a bridge from htaccess to jpg
 */
require_once '../class-alti-watermark-public.php';

$plugin = new Alti_Watermark_Public('0.3', parse_url($_GET['imageRequested'])['path'] );
echo $plugin->check_image_requested();