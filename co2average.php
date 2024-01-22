<?php
/*
  Plugin Name: CO2 calculation addon
  Plugin URI:  #
  Description: CO2 calculation helper,
  Version: 1.0
  Author: org100h
  Author URI: #
  License: GPLv2
  Text Domain: co2
 */

defined('\ABSPATH') || die('No direct script access allowed!');

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class co2average
{

    /**
     * The unique instance of the plugin.
     */

    private static $instance = null;
    private $prefix = 'co2';

    // Individual form
    private $form_ind_id = '2';

    // Small Business form
    private $form_biz_id = '7';

    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Register assets
        add_action('wp_enqueue_scripts', [$this, 'co2FrontAssets']);

        // Add Rest api endpoint to return average field value
        add_action('rest_api_init', [$this, 'co2GetAverage']);

        // Override Pdf template to add addition content
        add_action('gfpdf_core_template', [$this, 'co2PdfOverride'], 10, 3);

        // Register shortcode for confirmations chart
        add_shortcode('average', [$this, 'co2AverageShortcode']);

        // Register shortcode for pdf template
        add_shortcode('getChart', [$this, 'co2AveragePdfShortcode']);
    }

    /**
     * Return shortcode for average value
     */

    public function co2AverageShortcode($atts, $content = null)
    {
        $atts = shortcode_atts(
            array(
                'field-id' => '2',
                'total' => 0
            ),
            $atts,
            'average'
        );

        $html = '<span data-total="' . $atts['total'] . '" 
                       data-id="' . $atts['field-id'] . '"
                       class="chart-wrp chart-wrp-' . $atts['field-id'] . '"
                 ><canvas id="everage-' . $atts['field-id'] . '"></canvas>
                    <span id="loading-message-' . $atts['field-id'] . '">
                         <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                             <circle fill="rgba(30,122,196,1)" class="spinner_qM83" cx="4" cy="12" r="3"/>
                             <circle fill="rgba(30,122,196,1)"  class="spinner_qM83 spinner_oXPr" cx="12" cy="12" r="3"/>
                             <circle fill="rgba(30,122,196,1)"  class="spinner_qM83 spinner_ZTLf" cx="20" cy="12" r="3"/>
                         </svg>
                     </span>
                 </span>';
        return $html;
    }

    public function co2AveragePdfShortcode($atts, $content = null)
    {
        $atts = shortcode_atts(
            array(
                'field-id' => '2',
                'form-id' => 2,
                'total' => 0,
            ),
            $atts,
            'average'
        );

        $calc = $this->co2CalculateAverage(
            $atts['form-id'],
            $atts['field-id']
        );

        if (!$calc)
            return __('Error with average value calculation');

        $b = ($calc['avg'] / round($calc['max'])) * 100;

        $c = ($atts['total'] / round($calc['max'])) * 100;
        if ($b < 25 && $c < 25) {
            $b = $b * 7;
            $c = $c * 7; // Stupid but works
        }

        $html = '<div class="charts-wrp"> 
                     <div class="chart-title">' . __('Average', 'co2') . '</div>
                     <div class="chart-wrp chart-wrp-average">
                        <div style="width:' . round($b) . '%" class="chart-inner chart-inner-average">
                            <div class="chart-value" >' . round($calc['avg'], 2) . '</div>
                        </div>
                     </div>
                     
                     <div class="chart-title">' . __('Your', 'co2') . '</div>
                     <div class="chart-wrp chart-wrp-your">
                        <div style="width:' . round($c) . '%"  class="chart-inner chart-inner-your">
                            <div  class="chart-value" >' . round($atts['total'], 2) . ' </div>
                        </div>
                     </div>
                </div>';
        return $html;
    }


    /**
     * Load assets to site frontend
     */

    public function co2FrontAssets(): void
    {
        wp_enqueue_script($this->prefix . '-chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '1.0');
        wp_enqueue_script($this->prefix . '-chart-label-js', 'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js', [], '1.0', true);
        wp_enqueue_script($this->prefix . '-index', plugins_url('/assets/index.js', __FILE__), [], '1.0', true);


        wp_localize_script($this->prefix . '-index', $this->prefix . '_obj', [
            'prefix' => $this->prefix,
            'form_ind_id' => $this->form_ind_id,
            'form_biz_id' => $this->form_biz_id,
            'average' => __('Average', 'co2'),
            'your' => __('Your', 'co2'),

            'wpApiSettings' => [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest')
            ]
        ]);

        wp_enqueue_style($this->prefix . '-css', plugins_url('/assets/style.css', __FILE__));
    }

    /**
     * Register route for average calculation
     */
    public function co2GetAverage(): void
    {
        register_rest_route($this->prefix . '/v1', '/get_average', [
            'methods' => 'POST',
            'callback' => [$this, 'co2GetAverageCallback'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Callback for average co2 calculation
     */
    public function co2getAverageCallback($request): object
    {

        $formData = $request->get_json_params();
        $this->co2_log($formData);

        $form_id = (int) $formData['form_id'] ?? false;
        $field_id = (int) $formData['field_id'] ?? false;

        if (!$form_id || !$field_id)
            return rest_ensure_response(['error' => 'Empty params']);

        $average = $this->co2CalculateAverage($form_id, $field_id);

        return $average ?
            rest_ensure_response(['average' => round($average['avg'], 2)]) :
            rest_ensure_response(['error' => 'error']);
    }

    /*
     * TODO: Check form filter
     */
    public function co2PdfOverride($form, $entry, $settings)
    {


    }

    /*
     * Calculate average from all form submission by form id and field id
     */
    private function co2CalculateAverage($form_id, $field_id)
    {
        global $wpdb;

        $form_id = (int) $form_id ?? false;
        $field_id = (int) $field_id ?? false;


        $submissions_table = $wpdb->prefix . 'gf_entry';
        $entry_meta_table = $wpdb->prefix . 'gf_entry_meta';

        // Get all form submission
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT entry.id, meta.meta_value
             FROM $submissions_table as entry
             LEFT JOIN $entry_meta_table as meta ON entry.id = meta.entry_id
             WHERE entry.form_id = %d
                and meta.form_id = %d
                and meta.meta_key = %d",
                $form_id,
                $form_id,
                $field_id
            )
        );

        //$this->co2_log($submissions);

        $total = 0;
        $count = 0;
        $sub = [];

        // Calculate the sum of values and the number of submissions
        foreach ($submissions as $submission) {
            $total += floatval((float) $submission->meta_value);
            $sub[] = $submission->meta_value;
            $count++;
        }

        // Сalculate the average valueО
        $average = $total / $count;

        return ['avg' => $average, 'max' => max($sub)] ?? false;
    }

    /**
     * Simple logger
     */
    private function co2_log($entry, $mode = 'a', $file = 'co2')
    {
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['basedir'];
        // If the entry is array, json_encode.
        if (is_array($entry)) {
            $entry = json_encode($entry);
        }
        $file = $upload_dir . '/' . $file . '.log';
        $file = fopen($file, $mode);
        $bytes = fwrite($file, current_time('mysql') . "::" . $entry . "\n");
        fclose($file);
        return $bytes;
    }
}

/*
 * Load lang file
 */

add_action('plugins_loaded', function () {
    load_plugin_textdomain('co2', false, dirname(plugin_basename(__FILE__)) . '/lang/');
});

$co2average = co2average::get_instance();