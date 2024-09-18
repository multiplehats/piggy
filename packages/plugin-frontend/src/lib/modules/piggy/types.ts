import type { SpendRuleValueItem } from '@piggy/types/plugin/settings/adminTypes';

export interface Shop {
	uuid: string;
	name: string;
}

export type GetShopsResponse = Shop[];

export interface Rewards {
	uuid: string;
	title: string;
	requiredCredits: number;
	type: string;
	active: boolean;
	attributes: {
		expiration_duration: number;
		pre_redeemable: boolean;
		type: string;
	};
}

export interface Coupon {
	code: string;
	spend_rule: SpendRuleValueItem;
}

export type GetCouponsResponse = Coupon[];

export type GetRewardsResponse = Rewards[];

export interface Contact {
	uuid: string;
	email: string;
	subscriptions: {
		is_subscribed: boolean;
		status: string;
		type: {
			uuid: string;
			name: string;
			description: string;
			active: boolean;
			strategy: string;
		};
	}[];
	attributes: {
		age: number | null;
		tier: string | null;
		uuid: string;
		email: string;
		avatar: string;
		locale: string | null;
		status: string;
		address: string;
		channel: string;
		lastname: string;
		soe_name: string | null;
		birthdate: string | null;
		firstname: string;
		created_at: string;
		updated_at: string;
		wp_user_id: string;
		phonenumber: string;
		is_anonymous: boolean;
		add_to_wallet: string;
		loyalty_balance: number;
		prepaid_balance: number;
		list_memberships: string;
		present_in_imports: string;
		shopify_customer_id: string | null;
		custom_app_login_url: string;
		custom_app_login_code: string;
		loyalty_associated_shops: string;
		previous_loyalty_balance: number;
		default_contact_identifier: string | null;
		subscription_preferences_url: string;
		loyalty_last_transaction_date: string | null;
		loyalty_total_purchase_amount: number;
		loyalty_first_transaction_date: string | null;
		loyalty_number_of_transactions: number;
		loyalty_total_credits_received: number;
		loyalty_last_transaction_credits: number | null;
	};
	balance: {
		prepaid: number;
		credits: number;
	};
}

export type ClaimedReward = {
	id: string;
	wp_user_id: string;
	earn_rule_id: string;
	credits: string;
	timestamp: string;
};

export type GetContactResponse = {
	contact: Contact | null;
	claimedRewards: ClaimedReward[];
};
