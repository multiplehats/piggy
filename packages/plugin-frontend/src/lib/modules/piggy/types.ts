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
