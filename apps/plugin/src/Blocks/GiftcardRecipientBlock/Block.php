<?php

namespace Leat\Blocks\GiftcardRecipientBlock;

use Leat\Blocks\AbstractBlockIntegration;

class Block extends AbstractBlockIntegration
{
    public function get_name(): string
    {
        return 'giftcard-recipient';
    }

    public function get_script_data(): array
    {
        return [
            'fieldLabel' => __('Gift Card Recipient Email', 'leat-crm'),
            'fieldDescription' => __('Enter recipient email address', 'leat-crm'),
            'multipleGiftcardsNotice' => __('Important: You are purchasing multiple gift cards. All gift cards will be sent to the same recipient email address entered above. If you want to send gift cards to different recipients, please place separate orders.', 'leat-crm'),
        ];
    }
}
