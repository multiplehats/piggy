import { registerCheckoutBlock } from "@woocommerce/blocks-checkout";
import { getSetting } from "@woocommerce/settings";
import React from "react";
import "./style.scss";

const blockData = getSetting("leat-giftcard-recipient");
console.info("Gift Card Recipient Block Data:", blockData);

function GiftCardRecipientBlock() {
	return (
		<div className="gift-card-recipient">
			<label>{blockData.fieldLabel}</label>
			<input type="email" placeholder={blockData.fieldDescription} />
			<p className="notice">{blockData.multipleGiftcardsNotice}</p>
		</div>
	);
}

// Register the block with WooCommerce Blocks
registerCheckoutBlock({
	metadata: {
		name: "leat/giftcard-recipient",
		parent: ["woocommerce/checkout-fields-block"], // Specify where you want it to appear
	},
	component: GiftCardRecipientBlock,
});
