// This is the standard API response data when an error is returned.
export type ApiErrorResponse = {
	code: string;
	message: string;
	data?: ApiErrorResponseData | undefined;
};

// API errors contain data with the status, and more in-depth error details. This may be null.
export type ApiErrorResponseData = {
	status: number;
	params: Record<string, string>;
	details: Record<string, ApiErrorResponseDataDetails>;
} | null;

// The details object lists individual errors for each field.
export type ApiErrorResponseDataDetails = {
	code: string;
	message: string;
	data: ApiErrorResponseData;
	additional_errors: ApiErrorResponse[];
};
