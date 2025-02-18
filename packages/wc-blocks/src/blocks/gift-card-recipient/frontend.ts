import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { CART_STORE_KEY } from '@woocommerce/block-data';

const GiftCardRecipientField = ({ checkoutExtensionData }) => {
    const [recipientEmail, setRecipientEmail] = useState('');
    const [hasGiftcard, setHasGiftcard] = useState(false);

    const { cartItems } = useSelect((select) => {
        const store = select(CART_STORE_KEY);
        return {
            cartItems: store.getCartItems(),
        };
    });

    useEffect(() => {
        const giftcardInCart = cartItems.some(item => item.name.toLowerCase().includes('gift card'));
        setHasGiftcard(giftcardInCart);
    }, [cartItems]);

    if (!hasGiftcard) {
        return null;
    }

    return (
        <div className="leat-giftcard-recipient-wrapper">
            <TextControl
                label={__('Gift Card Recipient Email', 'leat')}
                value={recipientEmail}
                onChange={(value) => {
                    setRecipientEmail(value);
                    checkoutExtensionData.setValue('recipientEmail', value);
                }}
                type="email"
            />
        </div>
    );
};

registerCheckoutBlock({
    name: 'leat-giftcard-recipient',
    component: GiftCardRecipientField,
});