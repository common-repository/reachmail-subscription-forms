<?php
/*
Plugin Name: ReachMail Subscription Forms
Plugin URI: https://go.reachmail.net/Integration/WordPress-SubscriptionForms/
Description: Add ReachMail subscription forms to your WordPress site.
Version: 1.0.4
Author: ReachMail Inc.
Author URI: http://www.reachmail.net
License:  GNU General Public License v2
*/

class ReachMail_SubscriptionForm_Widget extends	WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            "reachMail_subscriptionform_widget",
            "ReachMail Subscription Form",
            array("description" => __("Add ReachMail subscription forms to your WordPress site", "text_domain"),)
        );
    }

    /**
    * Front-end display of widget.
    *
    * @see WP_Widget::widget()
    *
    * @param array $args     Widget arguments.
    * @param array $instance Saved values from database.
    */
    public function widget( $args, $instance )
    {
    ?>
		<p class="reachmailSubscriptionForm-title"><?php echo $instance["form_title"]; ?></p>
		<?php echo $instance["form_content"]; ?>
    <?php
    }

    /**
    * Back-end widget form.
    *
    * @see WP_Widget::form()
    *
    * @param array $instance Previously saved values from database.
    */
    public function form($instance)
    {
        $api_token = (isset($instance["api_token"])) ? $instance["api_token"] : "";
        $form_id = (isset($instance["form_id"])) ? $instance["form_id"] : "";
		$form_title = (isset($instance["form_title"])) ? $instance["form_title"] : "";
        ?>
            <p>
                API Token:
                <input type="text" name="<?php echo $this->get_field_name('api_token');?>" id="reachmail_api_token" value="<?php echo esc_attr($api_token); ?>" style="width: 100%;" />
            </p>

            <p>
				Form Id:
				<input type="text" name="<?php echo $this->get_field_name('form_id');?>" id="reachmail_form_id" value="<?php echo esc_attr($form_id); ?>" style="width: 100%;" />
            </p>

            <p>
				Title:
				<input type="text" name="<?php echo $this->get_field_name('form_title');?>" id="reachmail_form_title" value="<?php echo esc_attr($form_title); ?>" style="width: 100%;" />
            </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['api_token'] = (!empty($new_instance['api_token'])) ? strip_tags($new_instance['api_token']) : '';
        $instance['form_id'] = (!empty($new_instance['form_id'])) ? strip_tags($new_instance['form_id']) : '';
		$instance['form_title'] = (!empty($new_instance['form_title'])) ? strip_tags($new_instance['form_title']) : '';

        $subscription_form = get_subscription_form($instance['api_token'], $instance['form_id']);

        $instance['form_content'] = str_replace('REPLACE_POST_URL', plugins_url('form_post.php', __FILE__), $subscription_form["Content"]);

        return $instance;
    }
}

function get_subscription_form($api_token, $form_id)
{
    $args = array(
				'headers' => array(
								'Authorization' => 'token ' . $api_token,
								'Accept' => 'application/json'
							)
			);

	$url = 'https://services.reachmail.net/lists/subscriptionform/' . $form_id;

	$response = wp_remote_get($url, $args);

	try
	{
		$json = json_decode(wp_remote_retrieve_body($response), true);
	}
	catch(Exception $ex)
	{
		$json = null;
	}

	return $json;
}

function reachmail_shortcodes()
{
	$widget = get_option("widget_reachmail_subscriptionform_widget");

	foreach ($widget as $k => $v)
	{
		if (isset($v["api_token"]) && isset($v["form_id"]) && isset($v["form_content"]))
		{
			$widget_display = $v["form_content"];
			return $widget_display;
		}
	}

	return "";
}

function reachmail_register_widget()
{
    register_widget("ReachMail_SubscriptionForm_Widget");
}

function reachmail_register_shortcodes()
{
    add_shortcode("reachmail_subscriptionform", "reachmail_shortcodes");
}

add_action("widgets_init", "reachmail_register_widget");
add_action("init", "reachmail_register_shortcodes");

?>
