=== Shipment Tracking Plugin ===
Contributors: IVB
Tags: shipment, tracking, logistics, map, leaflet, courier
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete shipment tracking solution with interactive map, route visualization and tracking number search.

== Description ==

Shipment Tracking Plugin allows you to:

✔ Create unlimited Shipment posts  
✔ Add multiple route points (cities, addresses, date & time)  
✔ Automatically generate tracking numbers  
✔ Display interactive route map using Leaflet  
✔ Show route path (real road route, not straight line)  
✔ Display route timeline (Departure → In Transit → Final Destination)  
✔ Search shipments by Tracking Number via shortcode  
✔ Install demo shipment data  
✔ Reset demo data  

Perfect for logistics companies, couriers, freight services and delivery businesses.

== Features ==

- Custom Post Type: Shipment
- Tracking number auto generation
- Route builder in admin panel
- Draggable markers
- Automatic geocoding (OpenStreetMap Nominatim)
- Real road routing (Leaflet Routing Machine)
- Custom start / middle / final destination markers
- Timeline display
- Shipment search form
- Demo data installer
- Responsive layout

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. On activation:
   - 2 demo Shipment posts are created.
   - 1 page named "Tracking Search" is created with shortcode:
     [shipment_search]

4. Use shortcode:
   [shipment_search]-to display the shipment search form anywhere.
   [shipment_grid] - to display the shipment post grid.
   [shipment_search_form] - search from by tracking number

== Usage ==

1. Go to Shipment Posts → Add New.
2. Add route points (City, Address, Date/Time).
3. Tracking number is generated automatically.
4. View the shipment on the frontend.
5. Use Tracking Search page to search by tracking number.

== Admin Menu ==

The plugin adds:

- Shipment Posts
- Tracking Search
- Install Demo Data
- Reset Demo Data

== Shortcodes ==

[shipment_search]
Displays tracking search form and latest shipment by default.

== Frequently Asked Questions ==

= Does it use Google Maps? =

No. It uses OpenStreetMap + Leaflet (free and open-source).

= Can I add unlimited cities? =

Yes, unlimited route points are supported.

= Does it support international routes? =

Yes, global geocoding is supported.

== Screenshots ==

1. Shipment admin route builder
2. Interactive route map
3. Tracking search page
4. Timeline view

== Changelog ==

= 1.0.0 =
- Initial release
- Shipment CPT
- Route builder
- Tracking search
- Demo data installer
- Timeline view
- Map with real road routing

== Upgrade Notice ==

= 1.0.0 =
Initial stable release.
