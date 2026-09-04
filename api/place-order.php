<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!is_array($data)) {
        $data = $_POST;
    }

    $csrfToken = is_string($data['csrf_token'] ?? null) ? $data['csrf_token'] : ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf($csrfToken)) {
        // If session token is not matched or expired, regenerate a new one so retry works
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid session or CSRF token. Please try again.']);
        exit;
    }

    $customerName = trim((string)($data['customer_name'] ?? ''));
    $email        = trim((string)($data['email'] ?? ''));
    $phone        = trim((string)($data['phone'] ?? ''));
    $region       = trim((string)($data['region'] ?? ''));
    $town         = trim((string)($data['town'] ?? ''));
    $address      = trim((string)($data['address'] ?? ''));
    $items        = $data['items'] ?? [];

    if ($customerName === '' || $email === '' || $phone === '' || $region === '' || $town === '' || $address === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please fill in all required customer fields.']);
        exit;
    }

    if (!is_array($items) || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Your cart is empty.']);
        exit;
    }

    $cleanItems = [];
    $total = 0.0;

    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $id       = (string)($item['id'] ?? '');
        $name     = (string)($item['name'] ?? 'Product');
        $category = (string)($item['category'] ?? 'General');
        $price    = (float)($item['price'] ?? 0);
        $qty      = max(1, (int)($item['quantity'] ?? 1));
        
        if ($price <= 0 || $id === '') continue;

        $itemTotal = $price * $qty;
        $total += $itemTotal;

        $cleanItems[] = [
            'id'       => $id,
            'name'     => $name,
            'category' => $category,
            'price'    => $price,
            'quantity' => $qty,
        ];
    }

    if (empty($cleanItems)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid cart items.']);
        exit;
    }

    $orderData = [
        'user_id'       => !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
        'customer_name' => substr($customerName, 0, 100),
        'email'         => substr($email, 0, 150),
        'phone'         => substr($phone, 0, 20),
        'region'        => $region,
        'town'          => substr($town, 0, 100),
        'address'       => substr($address, 0, 300),
        'country'       => 'Ghana',
        'items'         => $cleanItems,
        'total'         => $total,
        'status'        => 'pending',
    ];

    $res = supabase_service_request('POST', '/rest/v1/orders', [$orderData]);

    if ($res === null || empty($res)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save order to database. Please try again.']);
        exit;
    }

    $createdOrder = is_array($res) && isset($res[0]) ? $res[0] : (is_array($res) ? $res : []);
    $orderId = (string)($createdOrder['id'] ?? ('ORD-' . time()));

    // Construct WhatsApp Message
    $rawWhatsapp = defined('WHATSAPP_NUMBER') && WHATSAPP_NUMBER !== '' ? WHATSAPP_NUMBER : '233544570238';
    $whatsappNumber = preg_replace('/[^0-9]/', '', $rawWhatsapp);

    $msg  = "📦 *New Order - " . APP_NAME . "*\n\n";
    $msg .= "*Order ID:* " . $orderId . "\n\n";
    $msg .= "*Customer:*\n";
    $msg .= "Name: " . $customerName . "\n";
    $msg .= "Email: " . $email . "\n";
    $msg .= "Phone: " . $phone . "\n";
    $msg .= "Region: " . $region . "\n";
    $msg .= "Town: " . $town . "\n";
    $msg .= "Address: " . $address . "\n\n";
    $msg .= "*Items:*\n";

    foreach ($cleanItems as $idx => $ci) {
        $itemSubtotal = number_format($ci['price'] * $ci['quantity'], 2, '.', '');
        $msg .= ($idx + 1) . ". " . $ci['name'] . " (" . $ci['category'] . ")\n";
        $msg .= "   Qty: " . $ci['quantity'] . " × ₵" . number_format($ci['price'], 2, '.', '') . " = ₵" . $itemSubtotal . "\n";
    }

    $msg .= "\n*Total: ₵" . number_format($total, 2, '.', '') . "*";

    $whatsappUrl = 'https://wa.me/' . $whatsappNumber . '?text=' . rawurlencode($msg);

    echo json_encode([
        'success'      => true,
        'order_id'     => $orderId,
        'whatsapp_url' => $whatsappUrl,
        'message'      => 'Order placed successfully!',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
