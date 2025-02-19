/**
 * External dependencies
 */
import React from "react";
import { registerPlugin } from "@wordpress/plugins";
import { ExperimentalOrderMeta } from "@woocommerce/blocks-checkout";
import { getSetting } from "@woocommerce/settings";

/**
 * Internal dependencies
 */
import "./style.scss";

const blockData = getSetting("leat-giftcard-recipient");

function GiftCardRecipient() {
	return (
		<ExperimentalOrderMeta>
			<div className="gift-card-recipient">
				<label>{blockData.fieldLabel}</label>
				<input type="email" placeholder={blockData.fieldDescription} />
				<p className="notice">{blockData.multipleGiftcardsNotice}</p>
			</div>
		</ExperimentalOrderMeta>
	);
}

registerPlugin("leat-giftcard-recipient", {
	render: GiftCardRecipient,
	scope: "woocommerce-checkout",
});
