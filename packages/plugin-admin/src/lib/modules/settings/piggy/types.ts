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
