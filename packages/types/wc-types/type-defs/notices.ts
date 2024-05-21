export interface NoticeType {
	id: string;
	content: string;
	status: 'success' | 'error' | 'info' | 'warning' | 'default';
	isDismissible: boolean;
	context?: string | undefined;
}
