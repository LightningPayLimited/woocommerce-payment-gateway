<?php
/**
 * Plugin Name: Lightning Pay Bitcoin Payment Gateway
 * Description: WooCommerce payment gateway for Bitcoin Lightning Network payments via Lightning Pay.
 * Version: 1.1.0
 * Author: Lightning Pay
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

// Declare compatibility with WooCommerce High-Performance Order Storage.
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Fix return URL key corruption from Lightning Pay appending ?status=success
add_filter('woocommerce_thankyou_order_key', function ($key) {
    $pos = strpos($key, '?');
    return $pos !== false ? substr($key, 0, $pos) : $key;
});

add_filter('woocommerce_payment_gateways', function ($gateways) {
    $gateways[] = 'WC_Gateway_Lightning_Pay';
    return $gateways;
});

add_action('wp_ajax_lightning_pay_check_status', function () {
    $gateway = WC()->payment_gateways()->payment_gateways()['lightning_pay'] ?? null;
    if ($gateway) {
        $gateway->admin_check_status();
    } else {
        wp_send_json_error(['message' => 'Lightning Pay gateway not found.']);
    }
});

add_action('admin_notices', function () {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    $settings = get_option('woocommerce_lightning_pay_settings', []);
    if (empty($settings['enabled']) || 'yes' !== $settings['enabled']) {
        return;
    }
    if (function_exists('get_woocommerce_currency') && get_woocommerce_currency() !== 'NZD') {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Lightning Pay:</strong> Your store currency is set to <strong>' . esc_html(get_woocommerce_currency()) . '</strong>, but Lightning Pay requires the currency to be set to <strong>NZD</strong>. ';
        echo 'Please <a href="' . esc_url(admin_url('admin.php?page=wc-settings')) . '">update your WooCommerce currency settings</a> to NZD, or the Lightning Pay payment method will not appear at checkout.';
        echo '</p></div>';
    }
});

add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    class WC_Gateway_Lightning_Pay_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
        protected $name = 'lightning_pay';

        public function initialize() {
            $this->settings = get_option('woocommerce_lightning_pay_settings', []);
        }

        public function is_active() {
            return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled']
                && !empty($this->settings['api_key'])
                && get_woocommerce_currency() === 'NZD';
        }

        public function get_payment_method_script_handles() {
            wp_register_script(
                'wc-lightning-pay-blocks',
                plugin_dir_url(__FILE__) . 'assets/js/lightning-pay-blocks.js',
                ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'],
                '1.0.1',
                true
            );
            return ['wc-lightning-pay-blocks'];
        }

        public function get_payment_method_data() {
            return [
                'title'       => $this->settings['title'] ?? 'Lightning Pay',
                'description' => $this->settings['description'] ?? 'Pay with Bitcoin via the Lightning Network.',
                'supports'    => ['products'],
            ];
        }
    }

    add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
        $registry->register(new WC_Gateway_Lightning_Pay_Blocks());
    });
});

add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_Lightning_Pay extends WC_Payment_Gateway {

        private string $api_key;
        private string $api_base;
        private bool $debug;

        public function __construct() {
            $this->id                 = 'lightning_pay';
            $this->method_title       = 'Lightning Pay';
            $this->method_description = 'Accept Bitcoin Lightning Network payments via Lightning Pay.';
            $this->has_fields         = false;
            $this->order_button_text  = 'Pay with Bitcoin';

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->api_key     = $this->get_option('api_key');
            $this->api_base    = rtrim($this->get_option('api_base', 'https://app.lightningpay.nz'), '/');
            $this->debug       = 'yes' === $this->get_option('debug');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            add_action('woocommerce_thankyou_' . $this->id, [$this, 'check_payment_status']);
            add_action('woocommerce_api_wc_gateway_lightning_pay', [$this, 'handle_return']);
            add_action('woocommerce_admin_order_data_after_order_details', [$this, 'render_admin_payment_info']);
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'   => 'Enable/Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Enable Lightning Pay',
                    'default' => 'no',
                ],
                'title' => [
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'Payment method title shown at checkout.',
                    'default'     => 'Lightning Pay',
                    'desc_tip'    => true,
                ],
                'description' => [
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'Payment method description shown at checkout.',
                    'default'     => 'Pay with Bitcoin via the Lightning Network.',
                    'desc_tip'    => true,
                ],
                'api_key' => [
                    'title'       => 'API Key',
                    'type'        => 'password',
                    'description' => 'Your Lightning Pay API key, found in your merchant profile.',
                    'desc_tip'    => true,
                ],
                'api_base' => [
                    'title'       => 'API Base URL',
                    'type'        => 'text',
                    'description' => 'Lightning Pay API base URL.',
                    'default'     => 'https://app.lightningpay.nz',
                    'desc_tip'    => true,
                ],
                'debug' => [
                    'title'   => 'Debug Logging',
                    'type'    => 'checkbox',
                    'label'   => 'Enable debug logging',
                    'default' => 'no',
                ],
            ];
        }

        public function admin_options() {
            echo '<div style="margin-bottom: 20px;">';
            echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/images/lightning-pay-logo.png') . '" alt="Lightning Pay" style="max-height: 50px; width: auto;">';
            echo '</div>';
            if (empty($this->api_key)) {
                echo '<div class="notice notice-info inline"><p>';
                echo '<strong>Getting started with Lightning Pay:</strong><br>';
                echo 'If you don\'t have an account, <a href="https://app.lightningpay.nz/auth/register" target="_blank">create one at lightningpay.nz</a>. Ensure you either select company account if you have a business bank account or enter your trading name if you are a sole trader.<br>';
                echo 'If you already have an account, enter your API key below.';
                echo '</p></div>';
            }
            if (get_woocommerce_currency() !== 'NZD') {
                echo '<div class="notice notice-warning inline"><p>';
                echo '<strong>Currency notice:</strong> Lightning Pay only supports <strong>NZD</strong> (New Zealand Dollar). ';
                echo 'Your store currency is currently set to <strong>' . esc_html(get_woocommerce_currency()) . '</strong>. ';
                echo 'Please <a href="' . esc_url(admin_url('admin.php?page=wc-settings')) . '">change your currency to NZD</a> for Lightning Pay to be available at checkout.';
                echo '</p></div>';
            }
            parent::admin_options();
        }

        public function is_available() {
            if (empty($this->api_key)) {
                return false;
            }
            if (get_woocommerce_currency() !== 'NZD') {
                return false;
            }
            return parent::is_available();
        }

        public function needs_setup() {
            return empty($this->api_key);
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                wc_add_notice('Payment error: order not found.', 'error');
                return ['result' => 'failure'];
            }

            $response = wp_remote_post($this->api_base . '/api/paymentlink', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'api-key'      => $this->api_key,
                ],
                'body'    => wp_json_encode([
                    'amount'    => $order->get_total(),
                    'ref'       => $order->get_order_number(),
                    'returnUrl' => $this->get_return_url($order),
                ]),
                'timeout' => 30,
            ]);

            if (is_wp_error($response)) {
                $this->log('API request failed: ' . $response->get_error_message());
                wc_add_notice('Payment error: unable to connect to payment provider.', 'error');
                return ['result' => 'failure'];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $code = wp_remote_retrieve_response_code($response);

            if ($code !== 200 || empty($body['paymentLink'])) {
                $this->log('API error (HTTP ' . $code . '): ' . wp_remote_retrieve_body($response));
                wc_add_notice('Payment error: unable to create payment link.', 'error');
                return ['result' => 'failure'];
            }

            $order->update_meta_data('_lightning_pay_reference', $body['reference']);
            $order->update_meta_data('_lightning_pay_link', $body['paymentLink']);
            $order->save();

            $order->update_status('pending', 'Awaiting Lightning Pay payment.');

            return [
                'result'   => 'success',
                'redirect' => $body['paymentLink'],
            ];
        }

        public function check_payment_status($order_id) {
            $order = wc_get_order($order_id);
            if (!$order || $order->get_payment_method() !== $this->id) {
                return;
            }
            if ($order->is_paid()) {
                return;
            }

            $reference = $order->get_meta('_lightning_pay_reference');
            if (empty($reference)) {
                return;
            }

            if ($this->verify_payment($reference)) {
                $order->payment_complete();
                return;
            }

            $nonce = wp_create_nonce('lightning_pay_check');
            $ajax_url = WC()->api_request_url('wc_gateway_lightning_pay');
            $ajax_url = add_query_arg([
                'order_id' => $order_id,
                'nonce'    => $nonce,
            ], $ajax_url);

            ?>
            <div id="lightning-pay-status">
                <p>Confirming your Lightning payment&hellip;</p>
            </div>
            <script type="text/javascript">
            (function() {
                var attempts = 0;
                var maxAttempts = 12;
                var statusEl = document.getElementById('lightning-pay-status');
                var interval = setInterval(function() {
                    attempts++;
                    if (attempts > maxAttempts) {
                        clearInterval(interval);
                        statusEl.innerHTML = '<p>Payment confirmation is taking longer than expected. Your order will update automatically once payment is confirmed.</p>';
                        return;
                    }
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', <?php echo wp_json_encode(esc_url_raw($ajax_url)); ?>);
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            try {
                                var data = JSON.parse(xhr.responseText);
                                if (data.paid) {
                                    clearInterval(interval);
                                    statusEl.innerHTML = '<p><strong>Payment confirmed!</strong></p>';
                                    setTimeout(function() { location.reload(); }, 1500);
                                }
                            } catch(e) {}
                        }
                    };
                    xhr.send();
                }, 5000);
            })();
            </script>
            <?php
        }

        public function handle_return() {
            if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'lightning_pay_check' ) ) {
                wp_send_json(['paid' => false], 403);
                return;
            }

            $order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json(['paid' => false], 404);
                return;
            }

            $current_user = wp_get_current_user();
            $order_user   = $order->get_user_id();
            if ($order_user && $current_user->ID !== $order_user) {
                wp_send_json(['paid' => false], 403);
                return;
            }

            if ($order->is_paid()) {
                wp_send_json(['paid' => true]);
                return;
            }

            $reference = $order->get_meta('_lightning_pay_reference');
            if (empty($reference)) {
                wp_send_json(['paid' => false]);
                return;
            }

            $paid = $this->verify_payment($reference);
            if ($paid) {
                $order->payment_complete();
            }

            wp_send_json(['paid' => $paid]);
        }

        public function render_admin_payment_info($order) {
            if ($order->get_payment_method() !== $this->id) {
                return;
            }

            $reference = $order->get_meta('_lightning_pay_reference');
            $payment_link = $order->get_meta('_lightning_pay_link');

            echo '<div class="order_data_column" style="border-top:1px solid #e5e5e5; padding-top:12px; margin-top:12px;">';
            echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/images/lightning-pay-logo.png') . '" alt="Lightning Pay" style="max-height: 30px; width: auto; margin-bottom: 8px;">';
            echo '<p><strong>Reference:</strong> ' . esc_html($reference ?: 'N/A') . '</p>';
            if ($payment_link) {
                echo '<p><strong>Payment Link:</strong> <a href="' . esc_url($payment_link) . '" target="_blank">View</a></p>';
            }
            echo '<p><strong>Status:</strong> <span id="lp-status">' . ($order->is_paid() ? 'Paid' : 'Pending') . '</span></p>';

            if (!$order->is_paid() && $reference) {
                $nonce = wp_create_nonce('lightning_pay_admin_check');
                ?>
                <button type="button" class="button" id="lp-check-status" data-order-id="<?php echo esc_attr($order->get_id()); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">Check Payment Status</button>
                <span id="lp-error" style="display:none; color:#d63638; margin-top:8px;"></span>
                <script type="text/javascript">
                document.getElementById('lp-check-status').addEventListener('click', function() {
                    var btn = this;
                    var errorEl = document.getElementById('lp-error');
                    btn.disabled = true;
                    btn.textContent = 'Checking...';
                    errorEl.style.display = 'none';
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', ajaxurl);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data.success && data.data.paid) {
                                document.getElementById('lp-status').textContent = 'Paid';
                                btn.textContent = 'Payment Confirmed';
                                setTimeout(function() { location.reload(); }, 1500);
                            } else if (data.success && !data.data.paid) {
                                btn.textContent = 'Not Yet Paid';
                                setTimeout(function() { btn.textContent = 'Check Payment Status'; btn.disabled = false; }, 2000);
                            } else {
                                var msg = (data.data && data.data.message) ? data.data.message : 'Unknown error';
                                errorEl.textContent = msg;
                                errorEl.style.display = 'block';
                                btn.textContent = 'Check Payment Status';
                                btn.disabled = false;
                            }
                        } catch(e) {
                            errorEl.textContent = 'Invalid response from server (HTTP ' + xhr.status + ')';
                            errorEl.style.display = 'block';
                            btn.textContent = 'Check Payment Status';
                            btn.disabled = false;
                        }
                    };
                    xhr.onerror = function() {
                        errorEl.textContent = 'Network error - could not reach server.';
                        errorEl.style.display = 'block';
                        btn.textContent = 'Check Payment Status';
                        btn.disabled = false;
                    };
                    xhr.send('action=lightning_pay_check_status&order_id=' + btn.dataset.orderId + '&nonce=' + btn.dataset.nonce);
                });
                </script>
                <?php
            }
            echo '</div>';
        }

        public function admin_check_status() {
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'lightning_pay_admin_check' ) ) {
                wp_send_json_error(['message' => 'Invalid or expired security token. Please reload the page and try again.']);
                return;
            }

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
                return;
            }

            $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(['message' => 'Order #' . $order_id . ' not found.']);
                return;
            }

            if ($order->is_paid()) {
                wp_send_json_success(['paid' => true]);
                return;
            }

            $reference = $order->get_meta('_lightning_pay_reference');
            if (empty($reference)) {
                wp_send_json_error(['message' => 'No Lightning Pay reference found for this order.']);
                return;
            }

            $result = $this->verify_payment_verbose($reference);
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
                return;
            }

            if ($result) {
                $order->payment_complete();
            }

            wp_send_json_success(['paid' => $result]);
        }

        private function verify_payment($reference) {
            $result = $this->verify_payment_verbose($reference);
            return $result === true;
        }

        private function verify_payment_verbose($reference) {
            $url = $this->api_base . '/api/merchant/payment?reference=' . urlencode($reference);

            $response = wp_remote_get($url, [
                'headers' => ['api-key' => $this->api_key],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                $this->log('Payment check failed: ' . $response->get_error_message());
                return new WP_Error('api_error', 'Could not connect to Lightning Pay API: ' . $response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            $raw_body = wp_remote_retrieve_body($response);
            $body = json_decode($raw_body, true);

            if ($code === 401 || $code === 403) {
                return new WP_Error('auth_error', 'Lightning Pay API authentication failed (HTTP ' . $code . '). Check your API key.');
            }

            if ($code !== 200) {
                $msg = isset($body['message']) ? $body['message'] : $raw_body;
                return new WP_Error('api_error', 'Lightning Pay API error (HTTP ' . $code . '): ' . $msg);
            }

            if ($body === null) {
                return new WP_Error('parse_error', 'Invalid JSON response from Lightning Pay API.');
            }

            return !empty($body['isPaid']) || !empty($body['result']['isPaid']);
        }

        private function log($message) {
            if ($this->debug) {
                wc_get_logger()->debug($message, ['source' => 'lightning-pay']);
            }
        }
    }
});
