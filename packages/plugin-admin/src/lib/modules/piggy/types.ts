export interface AdminSetApiKeyParams {
	api_key: string;
}

export interface AdminSetApiKeyResponse {
	api_key: string;
}

export interface AdminGetApiKeyResponse {
	api_key: string;
}

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

export type GetRewardsResponse = Rewards[];
