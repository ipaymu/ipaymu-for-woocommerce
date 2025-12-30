<?php

if (!defined("ABSPATH")) {
    exit();
}

class Ipaymu_WC_Gateway extends WC_Payment_Gateway
{
    public $id;
    public $method_title;
    public $method_description;
    public $icon;
    public $has_fields;
    public $redirect_url;
    public $auto_redirect;
    public $return_url;
    public $expired_time;
    public $title;
    public $description;
    public $url;
    public $va;
    public $secret;
    public $completed_payment;

    public function __construct()
    {
        $this->id = "ipaymu";
        $this->method_title = "iPaymu Payment";
        $this->method_description =
            "Pembayaran Virtual Account, QRIS, Alfamart/Indomaret, Direct Debit, Kartu Kredit, dan COD.";
        $this->has_fields = false;
        $this->icon = plugins_url("/ipaymu_badge.png", __FILE__);

        $default_return_url = home_url("/checkout/order-received/");
        $this->redirect_url = add_query_arg(
            "wc-api",
            "Ipaymu_WC_Gateway",
            home_url("/")
        );

        // Load the form fields and settings.
        $this->init_form_fields();
        $this->init_settings();

        // User settings.
        $this->enabled = $this->get_option("enabled");
        $this->auto_redirect = $this->get_option("auto_redirect", "60");
        $this->return_url = $this->get_option(
            "return_url",
            $default_return_url
        );
        $this->expired_time = $this->get_option("expired_time", 24);
        $this->title = $this->get_option("title", "Pembayaran iPaymu");
        $this->description = $this->get_option(
            "description",
            "Pembayaran melalui Virtual Account, QRIS, Alfamart/Indomaret, Direct Debit, Kartu Kredit, dan COD."
        );

        if ("yes" === $this->get_option("testmode", "yes")) {
            $this->url = "https://sandbox.ipaymu.com/api/v2/payment";
            $this->va = $this->get_option("sandbox_va");
            $this->secret = $this->get_option("sandbox_key");
        } else {
            $this->url = "https://my.ipaymu.com/api/v2/payment";
            $this->va = $this->get_option("production_va");
            $this->secret = $this->get_option("production_key");
        }

        $this->completed_payment =
            "yes" === $this->get_option("completed_payment", "no")
                ? "yes"
                : "no";

        // Hooks.
        add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
            $this,
            "process_admin_options",
        ]);
        // Register both the new and legacy API hooks so existing webhook
        // configurations continue to work after the class rename.
        add_action("woocommerce_api_ipaymu_wc_gateway", [
            $this,
            "check_ipaymu_response",
        ]);
        add_action("woocommerce_api_wc_gateway_ipaymu", [
            $this,
            "check_ipaymu_response",
        ]);
    }

    /**
     * Admin options fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            "enabled" => [
                "title" => __("Enable/Disable", "ipaymu-for-woocommerce"),
                "label" => "Enable iPaymu Payment Gateway",
                "type" => "checkbox",
                "description" => "",
                "default" => "yes",
            ],
            "title" => [
                "title" => __("Title", "ipaymu-for-woocommerce"),
                "type" => "text",
                "description" => "Nama Metode Pembayaran",
                "default" => "Pembayaran iPaymu",
                "desc_tip" => true,
            ],
            "description" => [
                "title" => __("Description", "ipaymu-for-woocommerce"),
                "type" => "textarea",
                "description" => "Deskripsi Metode Pembayaran",
                "default" =>
                    "Pembayaran melalui Virtual Account, QRIS, Alfamart / Indomaret, Direct Debit, Kartu Kredit, COD, dan lainnya ",
            ],
            "testmode" => [
                "title" => __("Mode Test/Sandbox", "ipaymu-for-woocommerce"),
                "label" => "Enable Test Mode / Sandbox",
                "type" => "checkbox",
                "description" =>
                    '<small>Mode Sandbox/Development digunakan untuk testing transaksi, jika mengaktifkan mode sandbox Anda harus memasukan API Key Sandbox (<a href="https://sandbox.ipaymu.com/integration" target="_blank">dapatkan API Key Sandbox</a>)</small>',
                "default" => "yes",
            ],
            "completed_payment" => [
                "title" => __(
                    "Status Completed After Payment",
                    "ipaymu-for-woocommerce"
                ),
                "label" => "Status Completed After Payment",
                "type" => "checkbox",
                "description" =>
                    "<small>Jika diaktifkan status order menjadi selesai setelah customer melakukan pembayaran. (Default: Processing)</small>",
                "default" => "no",
            ],
            "sandbox_va" => [
                "title" => "VA Sandbox",
                "type" => "text",
                "description" =>
                    '<small>Dapatkan VA Sandbox <a href="https://sandbox.ipaymu.com/integration" target="_blank">di sini</a></small>',
                "default" => "",
            ],
            "sandbox_key" => [
                "title" => "API Key Sandbox",
                "type" => "password",
                "description" =>
                    '<small>Dapatkan API Key Sandbox <a href="https://sandbox.ipaymu.com/integration" target="_blank">di sini</a></small>',
                "default" => "",
            ],
            "production_va" => [
                "title" => "VA Live/Production",
                "type" => "text",
                "description" =>
                    '<small>Dapatkan VA Production <a href="https://my.ipaymu.com/integration" target="_blank">di sini</a></small>',
                "default" => "",
            ],
            "production_key" => [
                "title" => "API Key Live/Production",
                "type" => "password",
                "description" =>
                    '<small>Dapatkan API Key Production <a href="https://my.ipaymu.com/integration" target="_blank">di sini</a></small>',
                "default" => "",
            ],
            "auto_redirect" => [
                "title" => __(
                    "Waktu redirect ke Thank You Page (time of redirect to Thank You Page in seconds)",
                    "ipaymu-for-woocommerce"
                ),
                "type" => "text",
                "description" => __(
                    "<small>Dalam hitungan detik. Masukkan -1 untuk langsung redirect ke halaman Anda</small>.",
                    "ipaymu-for-woocommerce"
                ),
                "default" => "60",
            ],
            "return_url" => [
                "title" => __("Url Thank You Page", "ipaymu-for-woocommerce"),
                "type" => "text",
                "description" => __(
                    "<small>Link halaman setelah pembeli melakukan checkout pesanan</small>.",
                    "ipaymu-for-woocommerce"
                ),
                "default" => home_url("/checkout/order-received/"),
            ],
            "expired_time" => [
                "title" => __(
                    "Expired kode pembayaran (expiry time of payment code)",
                    "ipaymu-for-woocommerce"
                ),
                "type" => "text",
                "description" => __(
                    "<small>Dalam hitungan jam (in hours)</small>.",
                    "ipaymu-for-woocommerce"
                ),
                "default" => "24",
            ],
        ];
    }

    /**
     * Process the payment and return the redirect URL.
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $buyerName = trim(
            $order->get_billing_first_name() .
                " " .
                $order->get_billing_last_name()
        );
        $buyerEmail = $order->get_billing_email();
        $buyerPhone = $order->get_billing_phone();

        $notifyUrl =
            $this->redirect_url .
            "&id_order=" .
            $order_id .
            "&param=notify&order_status=on-hold";
        if ("yes" === $this->completed_payment) {
            $notifyUrl =
                $this->redirect_url .
                "&id_order=" .
                $order_id .
                "&param=notify&order_status=completed";
        }

        $body = [
            "product" => ["Order #" . $order_id],
            "qty" => [1],
            "price" => [(float) $order->get_total()],
            "buyerName" => !empty($buyerName) ? $buyerName : null,
            "buyerPhone" => !empty($buyerPhone) ? $buyerPhone : null,
            "buyerEmail" => !empty($buyerEmail) ? $buyerEmail : null,
            "referenceId" => (string) $order_id,
            "returnUrl" => $this->return_url,
            "cancelUrl" =>
                $this->redirect_url .
                "&id_order=" .
                $order_id .
                "&param=cancel",
            "notifyUrl" => $notifyUrl,
            "expired" => (int) $this->expired_time,
            "expiredType" => "hours",
        ];

        $bodyJson = wp_json_encode($body, JSON_UNESCAPED_SLASHES);
        $requestBody = strtolower(hash("sha256", $bodyJson));
        $stringToSign =
            "POST:" . $this->va . ":" . $requestBody . ":" . $this->secret;
        $signature = hash_hmac("sha256", $stringToSign, $this->secret);

        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
            "va" => $this->va,
            "signature" => $signature,
        ];

        $response_http = wp_remote_post($this->url, [
            "headers" => $headers,
            "body" => $bodyJson,
            "timeout" => 60,
        ]);

        if (is_wp_error($response_http)) {
            $err_safe = sanitize_text_field(
                $response_http->get_error_message()
            );
            throw new Exception(
                sprintf(
                    /* translators: %s: HTTP error message. */
                    esc_html__("Request failed: %s", "ipaymu-for-woocommerce"),
                    esc_html($err_safe)
                )
            );
        }

        $res = wp_remote_retrieve_body($response_http);

        if (empty($res)) {
            throw new Exception(
                esc_html__(
                    "Request failed: empty response from iPaymu. Please contact support@ipaymu.com.",
                    "ipaymu-for-woocommerce"
                )
            );
        }

        $response = json_decode($res);

        if (
            empty($response) ||
            empty($response->Data) ||
            empty($response->Data->Url)
        ) {
            $message = isset($response->Message)
                ? $response->Message
                : "Unknown error";
            $message_safe = sanitize_text_field($message);
            throw new Exception(
                sprintf(
                    /* translators: %s: error message from iPaymu API. */
                    esc_html__(
                        "Invalid request. Response iPaymu: %s",
                        "ipaymu-for-woocommerce"
                    ),
                    esc_html($message_safe)
                )
            );
        }

        // Empty the cart.
        WC()->cart->empty_cart();

        return [
            "result" => "success",
            "redirect" => esc_url_raw($response->Data->Url),
        ];
    }

    /**
     * Handle callback / notify from iPaymu.
     */
    public function check_ipaymu_response()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $order_id = isset($_REQUEST["id_order"])
            ? absint($_REQUEST["id_order"])
            : 0;
        $is_webhook_post =
            isset($_SERVER["REQUEST_METHOD"]) &&
            "POST" === $_SERVER["REQUEST_METHOD"];
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $request_data = [];
        if ($is_webhook_post) {
            $request_data = $this->get_request_data_from_webhook();
        }

        // --- Handle Webhook POST Request (Server to Server) ---
        if ($is_webhook_post && !empty($request_data)) {
            if (!isset($request_data["reference_id"])) {
                if (defined("WP_DEBUG") && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log(
                        "[iPaymu Webhook] Missing reference_id in POST data."
                    );
                }
                status_header(400);
                echo "Missing reference_id";
                exit();
            }

            $order_id = absint($request_data["reference_id"]);
            $received_signature = $this->get_incoming_signature();

            if (empty($received_signature)) {
                if (defined("WP_DEBUG") && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log(
                        "[iPaymu Webhook] Missing signature header (X-Signature) or parameter."
                    );
                }
                status_header(401);
                echo "Missing signature";
                exit();
            }

            // Validasi Signature
            if (
                !$this->validate_ipaymu_signature(
                    $request_data,
                    $received_signature
                )
            ) {
                if (defined("WP_DEBUG") && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log("[iPaymu Webhook] Invalid signature detected.");
                }
                status_header(403);
                echo "Invalid signature";
                exit();
            }

            // --- Signature Valid: Process Payment Status ---
            if (
                isset($request_data["status"]) &&
                isset($request_data["trx_id"])
            ) {
                $order = wc_get_order($order_id);
                if (!$order) {
                    status_header(404);
                    echo "Order not found";
                    exit();
                }

                $status = sanitize_text_field(
                    wp_unslash($request_data["status"])
                );
                $ipaymu_trx_id = sanitize_text_field(
                    wp_unslash($request_data["trx_id"])
                );
                $order_status = isset($request_data["order_status"])
                    ? sanitize_text_field(
                        wp_unslash($request_data["order_status"])
                    )
                    : "processing";

                if ("berhasil" === strtolower($status)) {
                    $order->add_order_note(
                        sprintf(
                            /* translators: %s: iPaymu transaction ID. */
                            __(
                                "Payment Success iPaymu ID %s",
                                "ipaymu-for-woocommerce"
                            ),
                            $ipaymu_trx_id
                        )
                    );
                    if ("completed" === $order_status) {
                        $order->update_status("completed");
                    } else {
                        $order->update_status("processing");
                    }
                    $order->payment_complete();
                    echo "completed";
                    exit();
                } elseif ("pending" === strtolower($status)) {
                    if ("pending" === $order->get_status()) {
                        $order->add_order_note(
                            sprintf(
                                /* translators: %s: iPaymu transaction ID. */
                                __(
                                    "Waiting Payment iPaymu ID %s",
                                    "ipaymu-for-woocommerce"
                                ),
                                $ipaymu_trx_id
                            )
                        );
                        $order->update_status("pending");
                        echo "pending";
                    } else {
                        echo "order is " . esc_html($order->get_status());
                    }
                    exit();
                } elseif ("expired" === strtolower($status)) {
                    if ("pending" === $order->get_status()) {
                        $order->add_order_note(
                            sprintf(
                                /* translators: %s: iPaymu transaction ID. */
                                __(
                                    "Payment Expired iPaymu ID %s",
                                    "ipaymu-for-woocommerce"
                                ),
                                $ipaymu_trx_id
                            )
                        );
                        $order->update_status("cancelled");
                        echo "cancelled";
                    } else {
                        echo "order is " . esc_html($order->get_status());
                    }
                    exit();
                } else {
                    echo "invalid status";
                    exit();
                }
            } else {
                status_header(400);
                echo "Invalid POST data";
                exit();
            }
        }

        // --- Handle Browser Redirect (GET Request - User redirect back to website) ---
        // Bagian ini dijalankan saat User kembali dari iPaymu (Redirect)
        // Kita tidak perlu validasi signature ketat di sini karena update status sudah dihandle via Webhook (POST)

        if (!$order_id) {
            // Coba ambil order ID dari session jika tidak ada di URL
            if (
                isset(WC()->session) &&
                WC()->session->get("order_awaiting_payment") > 0
            ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $order_id = absint(
                    WC()->session->get("order_awaiting_payment")
                );
            }
        }

        if (!$order_id) {
            // Jika masih tidak ketemu order ID, redirect ke halaman Shop/Home
            wp_safe_redirect(wc_get_page_permalink("shop"));
            exit();
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_safe_redirect(wc_get_page_permalink("shop"));
            exit();
        }

        // Redirect ke halaman Order Received (Thank You Page)
        $order_received_url = wc_get_endpoint_url(
            "order-received",
            $order_id,
            wc_get_page_permalink("checkout")
        );
        if (
            "yes" === get_option("woocommerce_force_ssl_checkout") ||
            is_ssl()
        ) {
            $order_received_url = str_replace(
                "http:",
                "https:",
                $order_received_url
            );
        }
        $order_received_url = add_query_arg(
            "key",
            $order->get_order_key(),
            $order_received_url
        );

        $redirect = apply_filters(
            "ipaymu_wc_get_checkout_order_received_url",
            $order_received_url,
            $this
        );

        wp_safe_redirect($redirect);
        exit();
    }

    /**
     * Helper to get incoming signature from header or request data.
     */
    private function get_incoming_signature()
    {
        $received_signature = "";
        if (!empty($_SERVER["HTTP_X_SIGNATURE"])) {
            $received_signature = sanitize_text_field(
                wp_unslash($_SERVER["HTTP_X_SIGNATURE"])
            );
        } elseif (
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            !empty($_REQUEST["signature"])
        ) {
            $received_signature = sanitize_text_field(
                wp_unslash($_REQUEST["signature"])
            );
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
        }
        return $received_signature;
    }

    /**
     * Helper to get request data with detailed logging.
     */
    private function get_request_data_from_webhook()
    {
        $content_type = isset($_SERVER["CONTENT_TYPE"])
            ? sanitize_text_field(wp_unslash($_SERVER["CONTENT_TYPE"]))
            : "";
        $request_data = [];

        if (strpos($content_type, "application/json") !== false) {
            $raw_input = file_get_contents("php://input");
            $request_data = json_decode($raw_input, true);
        } else {
            // Untuk x-www-form-urlencoded
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $request_data = $_POST;

            // iPaymu seringkali menyertakan additional_info sebagai string "[]"
            // atau malah tidak terkirim jika kosong. Kita harus memastikan field wajib ada.
            if (!isset($request_data["additional_info"])) {
                $request_data["additional_info"] = []; // Inisialisasi sebagai array kosong
            }

            if (!isset($request_data["payment_no"])) {
                $request_data["payment_no"] = ""; // Inisialisasi sebagai string kosong
            }
        }

        if (!empty($request_data)) {
            $request_data = $this->normalize_webhook_data($request_data);
        }

        return $request_data;
    }

    /**
     * Normalize webhook data.
     */
    private function normalize_webhook_data($data)
    {
        // 1. Pastikan additional_info adalah array
        if (isset($data["additional_info"])) {
            if (is_string($data["additional_info"])) {
                $decoded = json_decode($data["additional_info"], true);
                $data["additional_info"] =
                    json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
        } else {
            $data["additional_info"] = [];
        }

        // 2. Pastikan payment_no ada (sering hilang di URL encoded)
        if (!isset($data["payment_no"])) {
            $data["payment_no"] = "";
        }

        // 3. Konversi tipe data numerik agar sesuai dengan JSON iPaymu
        $numeric_fields = [
            "trx_id",
            "paid_off",
            "status_code",
            "transaction_status_code",
        ];
        foreach ($numeric_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        // 4. Handle Boolean is_escrow
        if (isset($data["is_escrow"])) {
            $data["is_escrow"] = filter_var(
                $data["is_escrow"],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $data;
    }

    /**
     * Validate iPaymu callback signature.
     */
    private function validate_ipaymu_signature(
        $request_data,
        $received_signature
    ) {
        $va_number = $this->va;

        $data_to_validate = $request_data;
        unset($data_to_validate["signature"]);

        // Sort data by key ascending
        ksort($data_to_validate);

        // Hapus JSON_UNESCAPED_SLASHES agar hasil encode URL menjadi "http:\/\/..." (sama dengan iPaymu)
        $json_string = wp_json_encode(
            $data_to_validate,
            JSON_UNESCAPED_UNICODE
        );
        $expected_signature = hash_hmac("sha256", $json_string, $va_number);
        $normal_validation = hash_equals(
            $expected_signature,
            $received_signature
        );

        if (defined("WP_DEBUG") && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                "[iPaymu Debug] === SIGNATURE VALIDATION (Attempt 1: Normal) ==="
            );
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("[iPaymu Debug] Using VA: " . sanitize_text_field($va_number));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("[iPaymu Debug] String to Sign (JSON): " . sanitize_text_field($json_string));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("[iPaymu Debug] Expected Hash: " . sanitize_text_field($expected_signature));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("[iPaymu Debug] Received Hash: " . sanitize_text_field($received_signature));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                "[iPaymu Debug] Match Result: " .
                    ($normal_validation ? "YES (VALID)" : "NO (INVALID)")
            );
        }

        if (!$normal_validation) {
            // Try with cleaned data
            $clean_data = array_filter($data_to_validate, function ($value) {
                return !($value === "" || $value === null);
            });
            ksort($clean_data);

            $clean_json_string = wp_json_encode(
                $clean_data,
                JSON_UNESCAPED_UNICODE
            );
            $clean_expected_signature = hash_hmac(
                "sha256",
                $clean_json_string,
                $va_number
            );
            $clean_validation = hash_equals(
                $clean_expected_signature,
                $received_signature
            );

            if (defined("WP_DEBUG") && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(
                    "[iPaymu Debug] === SIGNATURE VALIDATION (Attempt 2: Cleaned) ==="
                );
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(
                    "[iPaymu Debug] String to Sign (Clean JSON): " .
                        sanitize_text_field($clean_json_string)
                );
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(
                    "[iPaymu Debug] Expected Hash: " . sanitize_text_field($clean_expected_signature)
                );
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(
                    "[iPaymu Debug] Match Result: " .
                        ($clean_validation ? "YES (VALID)" : "NO (INVALID)")
                );
            }

            return $clean_validation;
        }

        return $normal_validation;
    }
}
