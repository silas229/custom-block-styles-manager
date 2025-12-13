=== Custom Block Styles Manager ===
Contributors: silas2209
Donate link: https://github.com/sponsors/silas229
Tags: block-styles, gutenberg, custom-blocks
Requires at least: 5.3
Tested up to: 6.9
Stable tag: 1.0.1
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add custom block style variations with your own CSS.

== Description ==

Custom Block Styles Manager allows you to easily create and manage custom style variations for any WordPress Gutenberg block. With this plugin, you can add unique visual styles to your blocks through a user-friendly interface, without modifying your theme files.
= Key Features =

* **Create Custom Block Styles**: Add new style variations to any registered Gutenberg block
* **Visual Style Editor**: Use the built-in CSS editor with syntax highlighting to write custom styles
* **Easy Management**: Manage all your block styles from a centralized location in the WordPress admin
* **User-Friendly Interface**: Easily create and manage custom block styles with a streamlined CSS editor
* **CSS Class Preview**: See the generated CSS class name (is-style-{slug}) for each style
* **Organized Admin Interface**: Filter and sort block styles by block type
* **Quick Edit Support**: Quickly update block assignments using WordPress's quick edit feature
* **Theme Independent**: Styles are managed separately from your theme, making them portable across theme changes

= How It Works =

1. Navigate to Appearance > Block Styles in your WordPress admin
2. Create a new Block Style and give it a descriptive title
3. Select the target block you want to style (e.g., Paragraph, Heading, Image)
4. Write your custom CSS in the code editor
5. Publish the block style
6. The new style variation will appear in the block editor's style picker for the selected block

The plugin automatically registers your custom styles with WordPress and applies the CSS when a block uses your custom style. Each style gets a unique CSS class (is-style-{slug}) that you can use in your custom CSS.

= Use Cases =

* Create highlighted or callout paragraph styles
* Add custom button variations with unique colors and effects
* Design special heading styles for different content types
* Create custom image frame or border styles
* Build branded content blocks that match your design system

= Technical Details =

* Uses WordPress Custom Post Types (CPT) for style management
* Integrates seamlessly with the WordPress block editor
* CSS is sanitized for security
* Requires the `switch_themes` capability to manage block styles
* Compatible with all registered Gutenberg blocks

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Custom Block Styles Manager"
4. Click "Install Now" on the Custom Block Styles Manager plugin
5. After installation completes, click "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New
4. Click the "Upload Plugin" button at the top
5. Choose the downloaded ZIP file and click "Install Now"
6. After installation completes, click "Activate Plugin"

= After Activation =

1. Navigate to Appearance > Block Styles in your WordPress admin
2. Click "Add New" to create your first custom block style
3. Enter a title for your style (e.g., "Highlighted Paragraph")
4. Select the block you want to style from the dropdown menu
5. Write your custom CSS in the editor (a default selector like `.is-style-your-slug` will be provided)
6. Click "Publish" to make the style available
7. Open the block editor and select the block type you styled
8. Your custom style will appear in the block's style picker

== Frequently Asked Questions ==

= What is a block style variation? =

A block style variation is an alternative visual style for a WordPress block. For example, you might create different button styles (outlined, rounded, gradient) or paragraph styles (highlighted, callout, sidebar note). Users can select these variations from the block editor's style picker.

= Do I need to know CSS to use this plugin? =

Basic CSS knowledge is required, though you can start with simple properties and learn as you go. The plugin provides a starting template with the correct CSS class selector, making it easier to define your own styles.
= Will my custom styles work with any theme? =

Yes! Block styles are registered independently of your theme, so they will work regardless of which theme you're using. However, the appearance may vary slightly depending on your theme's base styles.

= Can I export my custom block styles? =

The block styles are stored as custom post types in your WordPress database. You can export them using WordPress's built-in export tool (Tools > Export) or with migration plugins that support custom post types.

= What happens if I switch themes? =

Your custom block styles will remain available. Since they're stored in the database and not in your theme, they persist across theme changes.

= Can I style core WordPress blocks? =

Yes! You can create custom styles for any registered block, including all core WordPress blocks like Paragraph, Heading, Image, Button, Group, and more.

= Can I style third-party plugin blocks? =

Yes! The plugin works with any registered Gutenberg block, whether it's from WordPress core, a theme, or a third-party plugin. Just select the block from the dropdown menu when creating a style.

= How do I delete a block style? =

Navigate to Appearance > Block Styles, find the style you want to delete, and move it to trash just like you would with a regular post or page.

= Can I duplicate an existing block style? =

While there's no built-in duplicate button, you can manually create a new block style and copy the CSS from an existing one. You can also use plugins that add duplication functionality to custom post types.

= Why doesn't my style appear in the block editor? =

Make sure the block style is published (not in draft status) and that you've selected the correct target block. Also verify that the block you're trying to style is registered and available in your block editor.

= Can I use this plugin on a multisite network? =

Yes, the plugin works on WordPress multisite installations. Each site in the network will have its own set of block styles.

= Is the custom CSS sanitized for security? =

Yes, the plugin sanitizes CSS input to prevent malicious code injection. However, users need the `switch_themes` capability to create or edit block styles, which is typically limited to administrators.

= Can I filter or search my block styles? =

Yes! The block styles list includes a filter dropdown that allows you to view styles for a specific block type. You can also use the built-in WordPress search functionality.

= Does this plugin affect site performance? =

The plugin has minimal performance impact. CSS is only loaded inline when a block uses a custom style, so unused styles don't add any overhead to your pages.

== Credits ==
This plugin uses a custom icon derived from Dashicons,
© WordPress contributors, licensed under GPLv2 or later.

== Screenshots ==

1. Block Styles management interface showing the list of custom styles with block type filtering
2. Creating a new block style with the CSS editor and block selector

== Changelog ==

= 1.0.0 =
* Initial release
* Create custom block style variations for any Gutenberg block
* CSS editor with syntax highlighting
* Block type filtering and quick edit support
* Admin interface under Appearance menu

== Upgrade Notice ==

= 1.0.0 =
Initial release of Custom Block Styles Manager.