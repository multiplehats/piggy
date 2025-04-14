export class LeatApiError extends Error {
	status: number;
	statusText: string;
	data: string;
	message: string;

	constructor(status: number, statusText: string, data: string) {
		super(`LeatApiError: ${statusText}`);
		this.status = status;
		this.statusText = statusText;
		this.data = data;
		this.message = data;
	}
}
