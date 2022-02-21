<?php
/**
 * Created by IntelliJ IDEA.
 * User: stephen
 * Date: 2022-02-21
 * Time: 11:03 AM
 */

namespace ucf_wp_email_notifier\settings;

if (is_multisite()){
	if (get_current_blog_id() == get_network()->site_id) {
		// Register the 'settings' page, but only on the primary blog for the network
		add_action( 'admin_menu', __NAMESPACE__ . '\\add_plugin_page' );
	} else {
	    // do nothing for subsites of the network that aren't the primary.
        // subsites use the primary site's settings.
        // I know you could use a network wide setting page instead, but that
        // requires a bunch of custom code.
    }
} else {
    // Register the 'settings' page on the one and only blog (not a multisite)
	add_action( 'admin_menu', __NAMESPACE__ . '\\add_plugin_page' );
}

add_action( 'admin_init', __NAMESPACE__ . '\\admin_init' );


// Add a link from the plugin page to this plugin's settings page
add_filter( 'plugin_row_meta', __NAMESPACE__ . '\\plugin_action_links', 10, 2 );



const option_group_name = 'ucf-wp-email-notifier-settings-group';

const section_email         = 'ucf-wp-email-notifier-email';
const section_post_types    = 'ucf-wp-email-notifier-post-types';

const page_title        = 'Post Change Email Notifier Settings'; //
const menu_title        = 'Post Change Email Notifier Settings';
const capability        = 'manage_options'; // user capability required to view the page
const page_slug         = 'ucf-wp-email-notifier-settings'; // unique page name, also called menu_slug



/**
 * Adds a link to this plugin's setting page directly on the WordPress plugin list page
 *
 * @param $links
 * @param $file
 *
 * @return array
 */
function plugin_action_links( $links, $file ) {
	if ( strpos( __FILE__, $file ) !== false ) {
		$links = array_merge(
			$links,
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . page_slug ) . '">' . __( 'Settings', page_slug ) . '</a>'
			)
		);
	}

	return $links;
}

/**
 * Tells WordPress about a new page and what function to call to create it
 */
function add_plugin_page() {
	error_log("adding plugin page");

	// This page will be under "Settings" menu. add_options_page is merely a WP wrapper for add_submenu_page specifying the 'options-general' menu as parent
	\add_options_page(
		page_title,
		menu_title,
		capability,
		page_slug,
		__NAMESPACE__ . '\\create_settings_page'
		 // since we are putting settings on our own page, we also have to define how to print out the settings
	);
}

/**
 * Get all post types and create settings for them
 */
function admin_init() {

	add_settings_sections();

	// add the email input in the email section
	add_email_setting();
	// add the post type checkboxes in the post type section
	$post_types = get_post_types(['public' => true], 'objects');
	foreach ($post_types as $post_object) {
		add_post_type_setting($post_object);
	}
}

function add_email_setting() {
	\add_settings_field(
		option_group_name . '-' . 'email',
		'Email address',
		__NAMESPACE__ . '\\setting_input_text',
		page_slug,
		section_email,
		array(
			'id' => option_group_name . '-' . 'email',
			'label' => "Email",
			'section' => section_email,
			'value' => get_database_settings_value_email()
		)
	);
	\register_setting(
		option_group_name,
		section_email
	);
}

/**
 * Adds a checkbox field for each post type
 *
 * @param \WP_Post_Type $post_object
 */
function add_post_type_setting( $post_object ) {
	// add setting, and register it

	$setting_id = unique_setting_id($post_object);
	\add_settings_field(
		$setting_id,  // Unique ID used to identify the field
		$post_object->name,  // The label to the left of the option.
		__NAMESPACE__ . '\\settings_input_checkbox',   // The name of the function responsible for rendering the option interface
		page_slug,                         // The page on which this option will be displayed
		section_post_types,         // The name of the section to which this field belongs
		array(   // The array of arguments to pass to the callback. These 4 are referenced in setting_input_checkbox.
		         'id'      => $setting_id, // copy/paste id here
		         'label'   => "Watch {$post_object->label}",
		         'section' => section_post_types,
		         'value'   => get_database_settings_value_post_type( $post_object )
		)
	);
	\register_setting(
		option_group_name,
		section_post_types
	);

}

/**
 * A unique identifier to save in the database. This uses the setting group plus the post slug.
 * @param $post_object
 *
 * @return string
 */
function unique_setting_id($post_object){
	return option_group_name . '-' . $post_object->name;
}

/**
 * Add the settings section for both built-in and custom post types (to distinguish between the two)
 */
function add_settings_sections() {

	\add_settings_section(
		section_email,
		"Email", // start of section text shown to user
		"Caution",
		page_slug
	);
	\add_settings_section(
		section_post_types,
		"Post Types to Watch",
		"No caution",
		page_slug
	);
}


function setting_input_text( $args ) {
	$html = "
        <input 
        type='hidden'   
        id='{$args['id']}' 
        name='{$args[ 'section' ]}[{$args[ 'id' ]}]' 
        value=''
        />
        <input
        type='email'
        id='{$args['id']}'
        name='{$args[ 'section' ]}[{$args[ 'id' ]}]'
        value='{$args['value']}'
        />
    ";

	echo $html;

}

/**
 * Creates the HTML code that is printed for each setting input
 *
 * @param $args
 */
function settings_input_checkbox( $args ) {
	// Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field.
	// Because we only call register_setting once, all the options are stored in an array in the database. So we
	// have to name our inputs with the name of an array. ex <input type="text" id=option_key name="option_group_name[option_key]" />.
	// WordPress will automatically serialize the inputs that are in this array form and store it under
	// the option_group_name field. Then get_option will automatically unserialize and grab the value already set and pass it in via the $args as the 'value' parameter.
	if ($args[ 'value' ]) {
		$checked = 'checked="checked"';
	} else {
		$checked = '';
	}

	// create a hidden variable with the same name and no value. if the box is unchecked, the hidden value will be POSTed.
	// If the value is checked, only the checkbox will be sent.
	// This way, we don't have to uncheck everything server-side and then re-check the POSTed values.
	// This is particularly useful to prevent preferences from being deleted if a post type is removed from a theme's code.
	// If we just unchecked everything, old post types would lose their preferences; if they are later reactivated, the preference
	// would be gone. This way, the preference persists.
	$html = "
        <input 
        type='hidden'   
        id='{$args['id']}' 
        name='{$args[ 'section' ]}[{$args[ 'id' ]}]' 
        value=''
        />
        <input
        type='checkbox'
        id='{$args['id']}'
        name='{$args[ 'section' ]}[{$args[ 'id' ]}]'
        value='{$args['id']}'
        {$checked}
        />
        <!-- Here, we will take the first argument of the array and add it to a label next to the input -->
        <label
        for='{$args['id']}'
        >
            {$args['label']}
        </label>
    ";

//	$html .= '<input type="hidden"   id="' . $args[ 'id' ] . '" name="' . $args[ 'section' ] . '[' . $args[ 'id' ] . ']" value=""/>';
//	$html .= '<input type="checkbox" id="' . $args[ 'id' ] . '" name="' . $args[ 'section' ] . '[' . $args[ 'id' ] . ']" value="' . ( $args[ 'id' ] ) . '" ' . $checked . '/>';

	// Here, we will take the first argument of the array and add it to a label next to the input
//	$html .= '<label for="' . $args[ 'id' ] . '"> ' . $args[ 'label' ] . '</label>';
	echo $html;
}

/**
 * Grabs the database value for the $settings_id option. The value is stored in a serialized array in the database.
 * It returns the value after sanitizing it.
 *
 * @param $setting_object
 *
 * @return string|void
 */
function get_database_settings_value_post_type( $setting_object ) {
	$data = \get_option( section_post_types );

	return \esc_attr( $data[ unique_setting_id($setting_object) ] );
}

function get_database_settings_value_email() {
	$data = \get_option(section_email);
	return \esc_attr( $data[ option_group_name . '-' . 'email']);
}

/**
 * Returns an array of post type names that are being watched.
 * @return array
 */
function get_watched_post_types_array() {
	$post_types = \get_post_types('', 'objects');
	$watched_post_types = [];
	foreach ($post_types as $post_object) {
		// get setting
		// if set to hide, then hide
		if (get_database_settings_value_post_type( $post_object )) {
		    $watched_post_types[] = $post_object->name;
		}
	}
	return $watched_post_types;
}

/**
 * Tells WordPress how to output the page
 */
function create_settings_page() {
	?>
	<div class="wrap" >

		<h2 ><?php echo page_title ?></h2 >

		<form method="post" action="options.php" >
			<?php
			// This prints out all hidden setting fields
			settings_fields( option_group_name );
			do_settings_sections( page_slug );
			submit_button();
			?>
		</form >
	</div >
	<?php
}