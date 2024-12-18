<?php

namespace Leat\Utils;

class OrderNotes {
    private const PREFIX = 'Leat: ';

    public static function add($order, $message, $type = 'info') {
        if (!$order) {
            return;
        }

        $formatted_message = self::PREFIX . $message;

        switch ($type) {
            case 'error':
                $formatted_message = '❌ ' . $formatted_message;
                break;
            case 'success':
                $formatted_message = '✅ ' . $formatted_message;
                break;
            case 'warning':
                $formatted_message = '⚠️ ' . $formatted_message;
                break;
        }

        $order->add_order_note($formatted_message);
    }

    public static function addError($order, $message) {
        self::add($order, $message, 'error');
    }

    public static function addSuccess($order, $message) {
        self::add($order, $message, 'success');
    }

    public static function addWarning($order, $message) {
        self::add($order, $message, 'warning');
    }
}