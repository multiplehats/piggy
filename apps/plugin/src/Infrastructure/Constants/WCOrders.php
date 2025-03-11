<?php

namespace Leat\Infrastructure\Constants;

class WCOrders
{
    // Related to selling gift cards
    public const GIFT_CARD_RECIPIENT_EMAIL = '_giftcard_recipient_email';
    public const GIFT_CARD_UUID = '_leat_giftcard_uuid';
    public const GIFT_CARD_CREATED = '_leat_giftcards_created';
    public const GIFT_CARD_TRANSACTION_ID = '_leat_giftcard_transaction_id';

    // Related to processing gift cards
    public const GIFT_CARD_PROCESSED = '_leat_processed_giftcard';
    public const GIFT_CARD_PROCESSED_TRANSACTION_ID_PREFIX = '_leat_processed_giftcard_transaction_id_';
    public const GIFT_CARD_REFUND_TRANSACTION_ID_PREFIX = '_leat_giftcard_refund_transaction_id_';
}
