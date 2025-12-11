=== Cirrusly Better Product Attachments ===
Contributors: cirrusly
Tags: woocommerce, attachments, downloads, product files, digital downloads, pdf, product documents
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 0.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional product attachments for WooCommerce. Features role-based access, download tracking, file expiry, email integration, and flexible positioning.

== Description ==

Cirrusly Better Product Attachments is a powerful yet lightweight WooCommerce extension that allows you to attach downloadable files to your products. Whether you sell software, courses, or physical goods with manuals, this plugin gives you full control over who accesses your files and when.

**Key Features:**

* **Unlimited Attachments:** Add as many files as you need to any WooCommerce product.
* **Download Analytics:** Track exactly how many times each file has been downloaded directly from the product editor.
* **Role-Based Access:** Restrict downloads to specific user roles (Everyone, Logged In Users, Guests, Customers, or Administrators).
* **Flexible Positioning:** Automatically display attachments at the bottom of the description, in a dedicated "Downloads" tab, or after the "Add to Cart" button.
* **Shortcode Support:** Use `[cirrusly_attachments]` to place your file list anywhere on the product page (compatible with Elementor, Divi, and other builders).
* **Email Integration:** Automatically include download links in the "Order Completed" emails sent to customers.
* **Order Status Unlocking:** Control when a file becomes available based on order status (e.g., only unlock after payment is "Completed").
* **File Expiry:** Set specific expiration dates for individual files.
* **Smart Icons:** Automatically detects and displays icons for PDF, Zip, Video, Image, Audio, and Excel files.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/cirrusly-better-product-attachments` directory, or install the plugin through the WordPress plugins screen.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to any WooCommerce product to start adding attachments via the "Product Downloads & Attachments" meta box.

== Frequently Asked Questions ==

= Can I use files hosted externally? =
Yes. You can upload files to your WordPress Media Library or simply paste a URL from an external source like Dropbox, Google Drive, or Amazon S3.

= How do I track downloads? =
Go to the product edit screen. In the "Product Downloads & Attachments" section, you will see a "Dl's" column that shows the total download count for each file.

= Can I hide files from the product page but show them after purchase? =
Yes. Set the "Visibility" option for the file to "Hidden". The customer will only see the download link on the "Order Received" page and in their email after they purchase the product.

= Does this work with page builders? =
Yes. You can use the shortcode `[cirrusly_attachments]` to place the download list inside any text module or shortcode widget within Elementor, Divi, Beaver Builder, etc.

== Screenshots ==

1. **Product Edit Screen** - Easily add files, set permissions, and view download counts.
2. **Frontend Display** - Clean, professional list of downloads with file type icons.
3. **Order View** - How downloads appear to customers after purchase.

== Changelog ==

= 1.0.0 =
* Initial release.