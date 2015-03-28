<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Heartbeat API Path Class

 */
class Heartbeat_API_Path
{
    /**
     * @var string
     */
    public $token;
    /**
     * @var string
     */
    public $plugin_url;
    /**
     * @var
     */
    public $version;

    /**
     * Constructor.
     * @since  1.0.0
     * @return  void
     */
    public function __construct($file)
    {
        // Class variables
        $this->token = 'heartbeat-api-path';
        $this->plugin_url = trailingslashit(plugins_url('', $file));

        // Actions & filters
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
        add_action('wp_footer', array(&$this, 'enqueue_scripts'));
        add_filter('heartbeat_settings', array(&$this, 'heartbeat_settings'));
        add_action('wp_dashboard_setup', array(&$this, 'example_add_dashboard_widgets'));
        add_action('wp_print_footer_scripts', array(&$this, 'path_heartbeat_footer_js'), 20);
        add_action('admin_print_footer_scripts', array(&$this, 'path_admin_heartbeat_footer_js'), 20);

    } // End __construct()

    /**
     * Initialise the code.
     * @since  1.0.0
     * @return void
     */
    public function init()
    {

        // Heartbeat filters
        add_filter('heartbeat_received', array(&$this, 'respond_to_browser_authenticated'), 5, 2);
        add_filter('heartbeat_nopriv_received', array(&$this, 'respond_to_browser_unauthenticated'), 5, 2);

    } // End init()

    /**
     * Enqueue frontend JavaScripts.
     * @since  1.0.0
     * @return void
     */
    public function enqueue_scripts()
    {

        // Load the path javascript
        wp_enqueue_script($this->token . '-path', $this->plugin_url . 'assets/js/path.js', array('jquery', 'heartbeat'), '1.0.0', true);

    } // End enqueue_scripts()

    /**
     * Enqueue frontend CSS files.
     * @since  1.0.0
     * @return void
     */
    public function enqueue_styles()
    {

        // Load the path frontend CSS
        wp_register_style($this->token . '-frontend', $this->plugin_url . 'assets/css/frontend.css', '', '1.0.0', 'screen');
        wp_enqueue_style($this->token . '-frontend');

    } // End enqueue_styles()

    /**
     * Sets heartbeat tick interval.
     * @since  1.0.0
     * @return void
     */
    public function heartbeat_settings($settings)
    {

        $settings['interval'] = "fast";
        return $settings;

    } // End heartbeat_settings

    /**
     * Handle send data and respond to the browser.
     * @since  1.0.0
     * @return $response data to the heartbeat tick function
     */
    public function respond_to_browser_unauthenticated($response, $data)
    {

        $guestPath = get_option("guest_path");


        $currentGuest = $guestPath[$data['ipaddress']];
        if (count($currentGuest) >= 30) {
            array_pop($currentGuest);
        }
        $guestPath[$data['ipaddress']][] = array("time" => $data['sent_time'], "page_uri" => $data['current_page_uri']);


        update_option("guest_path", $guestPath);

        // Do custom code here
        $data['greeting'] = 'Hey You! ';

        $response = $data;

        return $response;

    } // End respond_to_browser_unauthenticated()

    /**
     * Handle authenticated user (logged in) send data and respond to the browser.
     * @since  1.0.0
     * @return $response data to the heartbeat tick function
     */
    public function respond_to_browser_authenticated($response, $data)
    {

        $guestPath = get_option("guest_path");

        $path = '';
        $cnt = 0;
        foreach ($guestPath as $key => $paths) {
            $path .= "<h2>" . $key . "</h2>";
            $path .= "<ol>";

            foreach ($paths as $p) {
                if ($p['page_uri'] != $prevUri) {
                    $path .= "<li>" . $p['page_uri'] . "</li>";
                    if ($cnt > 10) {
                        break;
                    }
                    $cnt++;
                }
                $prevUri = $p['page_uri'];
            }
            $path .= "</ol>";


        }

        $data['data_to_display'] = $path;

        $response = $data;


        return $response;

    } // End respond_to_browser_authenticated()

    /**
     * Add a widget to the dashboard.
     *
     * This function is hooked into the 'wp_dashboard_setup' action below.
     */
    function example_add_dashboard_widgets()
    {

        wp_add_dashboard_widget(
            'example_dashboard_widget',         // Widget slug.
            'SimpleLytics - Real-Time-ish Analytics',         // Title.
            array(&$this, 'example_dashboard_widget_function') // Display function.
        );
    }

    /**
     * Create the function to output the contents of our Dashboard Widget.
     */
    function example_dashboard_widget_function()
    {

        // Display whatever it is you want to show.
        echo "<div id='simpleLytics'>Please wait for analytics to start.</div>";
    }

    // Inject our JS into the admin footer
    /**
     *
     */
    function path_heartbeat_footer_js()
    {
        ?>
        <script>
            (function ($) {
                var landingServerTime;

                $(document).on('heartbeat-send', function (e, data) {
                    data['path_heartbeat'] = 'dashboard_summary';
                    data['current_page_uri'] = "<?php echo $_SERVER['REQUEST_URI']?>";
                    data['send_time'] = Math.round(e.timeStamp / 1000);
                    data['ipaddress'] = "<?php echo $_SERVER['REMOTE_ADDR']?>";
                });

                // Listen for the custom event "heartbeat-tick" on $(document).
                $(document).on('heartbeat-tick', function (e, data) {
                    if (!landingServerTime) {
                        landingServerTime = data['server_time']
                    }
                    ;
                    if (data['server_time'] > (landingServerTime + 30)) {
                        alert(data['greeting'] + 'Did you know that ' + (data['server_time'] - landingServerTime) + ' seconds passed just now? Welcome Back!');
                        landingServerTime = (data['server_time']);

                    }
                    console.log(data);

                });
            }(jQuery));
        </script>
    <?php
    }

    /**
     *
     */
    function path_admin_heartbeat_footer_js()
    {
        global $pagenow;

        // Only proceed if on the dashboard
        if ('index.php' != $pagenow)
            return;
        ?>
        <script>
            (function ($) {

                // Hook into the heartbeat-send
                $(document).on('heartbeat-send', function (e, data) {
                    data['path_heartbeat'] = 'dashboard_summary';
                });

                // Listen for the custom event "heartbeat-tick" on $(document).
                $(document).on('heartbeat-tick', function (e, data) {

                    // Only proceed if our EDD data is present
                    if (!data['data_to_display'])
                        return;

                    // Log the response for easy proof it works
                    console.log(data['data_to_display']);

                    // Update sale count and bold it to provide a highlight
                    $('#example_dashboard_widget #simpleLytics').html(data['data_to_display']).css('font-weight', 'bold');

                    // Return font-weight to normal after 2 seconds
                    setTimeout(function () {
                        $('#example_dashboard_widget #simpleLytics').css('font-weight', 'normal');
                        ;
                    }, 2000);

                });
            }(jQuery));
        </script>



    <?php
    }

} // End Heartbeat_API_Path

