<?php
/**
 * Plugin Name: Simple WA Floating Button
 * Description: Allow your customers to chat with you in one click!
 * Author: YourAlly Agency
 * Author URI: https://yourally.dev
 * Version: 1.0.0
 * License: GPL-3.0
 */

if ( ! defined('ABSPATH') ) {
  exit;
}

class LWAFB_Plugin {
  private $option_key = 'lwafb_options';
  private $plugin_version = '1.0.0';
  private $text_domain = 'lwafb_plugin';

  public function __construct() {
    add_action( 'admin_menu', [$this, 'add_settings_page'] );
    add_action( 'admin_init', [$this, 'register_settings'] );
    add_action( 'wp_enqueue_scripts', [$this, 'enqueue_public_assets'] );
    add_action( 'wp_footer', [$this, 'render_button'] );
  }

  public function enqueue_public_assets() {
    if ( ! is_admin() ) {
      wp_enqueue_style( 'lwafb-styles', plugin_dir_path( __FILE__ ) . 'styles.min.css', array(), $this->plugin_version );
    }
  }

  public function add_settings_page() {
    add_options_page(
      'Simple WA Button',
      'Simple WA Button',
      'manage_options',
      'lwafb_settings',
      [$this, 'settings_page']
    );
  }

  public function settings_page() {
    ?>
    <div class="wrap">
      <h1>Simple WA Floating Button</h1>
      <form action="options.php" method="post">
        <?php
        settings_fields( 'lwafb_settings_group' );
        do_settings_sections( 'lwafb_settings' );
        submit_button( 'Save changes' );
        ?>
      </form>
    </div>
    <?php
  }

  public function register_settings() {
    register_setting( 'lwafb_settings_group', $this->option_key, [$this, 'sanitize'] );

    add_settings_section(
      'lwafb_section',
      'Button Settings',
      function () {
        echo "<p>Fill all fields to show the Floating Button.</p>";
      },
      'lwafb_settings'
    );

    add_settings_field(
      'show_button',
      'Show button',
      function () {
        $options = get_option($this->option_key);
        $checked = !empty($options['show_button']) ? 'checked' : '';
        printf(
          '<input type="checkbox" name="%1$s[show_button]" value="1" %2$s />',
          esc_attr( $this->option_key ),
          checked( $checked, 'checked', false )
        );
      },
      'lwafb_settings',
      'lwafb_section'
    );

    add_settings_field(
      'country_code',
      'Country code',
      function () {
        $options = get_option($this->option_key);
        $selected = isset( $options['country_code'] ) ? $options['country_code'] : '';
        $codes = [
          '57' => '+57 (Colombia)',
          '1' => '+1 (USA)',
        ];
        printf(
          '<select name="%1$s[country_code]">',
          esc_attr( $this->option_key )
        );
        foreach ($codes as $code => $label) {
          printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr( $code ),
            selected($selected, $code, false),
            esc_attr( $label )
          );
        }
        printf('</select>');
      },
      'lwafb_settings',
      'lwafb_section'
    );

    add_settings_field(
      'phone',
      'Phone number',
      function () {
        $options = get_option($this->option_key);
        printf(
          '<input required type="text" name="%1$s[phone]" value="%2$s" placeholder="3001234567" />',
          esc_attr( $this->option_key ),
          esc_attr( $options['phone'] )
        );
      },
      'lwafb_settings',
      'lwafb_section'
    );

    add_settings_field(
      'message',
      'Message (optional)',
      function () {
        $options = get_option($this->option_key);
        printf(
          '<textarea rows="4" name="%1$s[message]" placeholder="%2$s" style="resize: none">%3$s</textarea>',
          esc_attr( $this->option_key ),
          esc_html__( 'Short message', $this->text_domain ),
          esc_attr( $options['message'] )
        );
      },
      'lwafb_settings',
      'lwafb_section'
    );

    add_settings_field(
      'label',
      'Label (optional)',
      function () {
        $options = get_option($this->option_key);
        printf(
          '<input type="text" name="%1$s[label]" value="%2$s" placeholder="Chat with us!" />',
          esc_attr( $this->option_key ),
          esc_attr( $options['label'] )
        );
      },
      'lwafb_settings',
      'lwafb_section'
    );

    add_settings_field(
      'position',
      'Position',
      function () {
        $options = get_option( $this->option_key );
        $position = isset( $options['position'] ) ? $options['position'] : 'right';

        printf(
          '<label style="margin-right:1rem"><input type="radio" name="%1$s[position]" value="left" %2$s /> %3$s</label>
          <label><input type="radio" name="%1$s[position]" value="right" %4$s /> %5$s</label>',
          esc_attr( $this->option_key ),
          checked( $position, 'left', false ),
          esc_html__( 'Left', $this->text_domain ),
          checked( $position, 'right', false ),
          esc_html__( 'Right', $this->text_domain )
        );
      },
      'lwafb_settings',
      'lwafb_section'
    );
  }

  public function sanitize($input) {
    return [
      'show_button' => isset( $input['show_button'] ) ? 1 : 0,
      'country_code' => sanitize_text_field( $input['country_code'] ),
      'phone' => sanitize_text_field( $input['phone'] ),
      'label' => sanitize_text_field( $input['label'] ),
      'message' => sanitize_text_field( $input['message'] ),
      'position' => in_array( $input['position'], ['left', 'right'] ) ? $input['position'] : 'right'
    ];
  }

  public function render_button() {
    $options = get_option( $this->option_key );

    if (
      empty($options['show_button']) ||
      empty($options['country_code']) ||
      empty($options['phone'])
    ) {
      return;
    }

    $phone = preg_replace('/\D/', '', $options['phone']);
    $full_number = $options['country_code'] . $phone;
    $message = !empty($options['message']) ? urlencode($options['message']) : '';
    $label = !empty($options['label']) ? $options['label'] : 'Chat with us!';
    $position = $options['position'] === 'left' ? 'p-left' : 'p-right';
    $url = "https://wa.me/$full_number?text=$message";

    printf(
      '<style>.waf-button::after{content:"%1$s"}</style><a class="waf-button %2$s" href="%3$s" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 32 32" fill="white"><path d="M16 .396C7.166.396 0 7.562 0 16.396c0 2.896.76 5.593 2.084 7.948L0 32l7.832-2.052A15.894 15.894 0 0016 32c8.832 0 16-7.166 16-15.604S24.832.396 16 .396zm0 29.052a13.34 13.34 0 01-6.844-1.876l-.48-.292-4.636 1.224 1.236-4.52-.312-.468a13.248 13.248 0 01-2.02-6.92c0-7.344 5.98-13.312 13.36-13.312 7.348 0 13.32 5.968 13.32 13.312.004 7.344-5.972 13.352-13.324 13.352zm7.3-9.676c-.4-.2-2.356-1.164-2.72-1.296-.36-.128-.624-.2-.888.2-.264.4-1.02 1.296-1.252 1.56-.228.264-.456.3-.848.1-.396-.2-1.676-.616-3.192-1.964-1.18-1.052-1.976-2.352-2.208-2.748-.228-.4-.024-.616.176-.816.18-.18.396-.468.6-.7.2-.24.264-.4.396-.66.132-.264.068-.5-.032-.7-.1-.2-.888-2.14-1.216-2.92-.32-.772-.648-.668-.888-.68-.228-.012-.484-.012-.74-.012-.264 0-.692.1-1.052.5s-1.38 1.348-1.38 3.28c0 1.932 1.412 3.792 1.608 4.056.2.264 2.784 4.26 6.748 5.976.944.408 1.68.652 2.256.832.948.3 1.812.256 2.492.156.76-.112 2.356-.964 2.688-1.896.332-.932.332-1.732.232-1.896-.096-.16-.364-.264-.76-.464z"></path></svg><span class="notification"></span></a>',
      esc_attr( $label ),
      esc_attr( $position ),
      esc_attr( $url )
    );
  }
}

new LWAFB_Plugin();
