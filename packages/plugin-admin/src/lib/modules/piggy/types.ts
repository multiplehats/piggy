export type Shop = {
	uuid: string;
	name: string;
};

export type GetShopsResponse = Shop[];

export type Rewards = {
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
};

export type GetRewardsResponse = Rewards[];
