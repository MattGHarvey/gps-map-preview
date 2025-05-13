<?php
/*
Plugin Name: GPS Map Preview
Description: Displays an OpenStreetMap map on the post edit screen based on a custom GPS field.
Version: 1.0
Author: Matt Harvey
Author URI: https://robotsprocket.com
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Usage 
    1. Add a custom field named "GPS" to your post with the format "lat,lng" (e.g., "37.7749,-122.4194").
    2. Edit a post and see the map preview in the sidebar.
    3. The map will display the location based on the GPS coordinates provided.
    4. The map is interactive and allows zooming and panning.
    5. The map will automatically resize when the window is resized.
*/

add_action('add_meta_boxes', 'gps_map_add_meta_box');
function gps_map_add_meta_box() {
    add_meta_box('gps_map_preview', 'GPS Map Preview', 'gps_map_render_meta_box', 'post', 'side', 'default');
}

function gps_map_render_meta_box($post) {
    $gps = get_post_meta($post->ID, 'GPS', true);
    if (!$gps) {
        echo "<p>No GPS coordinates found.</p>";
        return;
    }

    // Extract lat/lng
    if (preg_match('/^\s*([-+]?[0-9]*\.?[0-9]+),\s*([-+]?[0-9]*\.?[0-9]+)\s*$/', $gps, $matches)) {
        $lat = $matches[1];
        $lng = $matches[2];
    } else {
        echo "<p>Invalid GPS format. Expected: lat,lng</p>";
        return;
    }

    // Output container + JS
    ?>
<div id="gps-map" style="height: 600px; width: 100%;"></div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    function initializeMap() {
        var mapContainer = document.getElementById('gps-map');
        if (!mapContainer.offsetWidth || !mapContainer.offsetHeight) {
            // Try again shortly if the container isn't sized yet
            return setTimeout(initializeMap, 100);
        }

        var map = L.map('gps-map', {
            scrollWheelZoom: false // 👈 disables scroll zoom
        }).setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);
        L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>]).addTo(map);
        map.invalidateSize(); // Force correct rendering
    }

    initializeMap();
});
</script>
<?php
}
add_action('admin_enqueue_scripts', 'gps_map_enqueue_leaflet');
function gps_map_enqueue_leaflet($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

    // Leaflet assets
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');

    // Your custom plugin CSS
    wp_enqueue_style('gps-map-plugin-css', plugin_dir_url(__FILE__) . 'css/gps-map.css');
}