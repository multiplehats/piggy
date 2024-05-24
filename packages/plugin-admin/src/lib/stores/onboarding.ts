import OnboardingAccount from '$lib/components/onboarding/onboarding-account.svelte';
import OnboardingConnectAccount from '$lib/components/onboarding/onboarding-connect-account.svelte';
import type { SvelteComponent } from 'svelte';
import { derived, writable } from 'svelte/store';

export const OnboardingStepId = {
	welcome: 'welcome',
	connectAccount: 'connect-account',
	generalSettings: 'general-settings'
} as const;

export type OnboardingStepId = (typeof OnboardingStepId)[keyof typeof OnboardingStepId];

export interface Step {
	id: OnboardingStepId;
	title: string;
	href: string;
	status: 'completed' | 'current' | 'upcoming';
	showActions: boolean;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	component?: typeof SvelteComponent<any>;
}

export const initialOnboardingSteps = [
	{
		id: 'welcome',
		title: 'Welcome',
		href: '/onboarding?step=welcome',
		status: 'current',
		showActions: false,
		component: OnboardingAccount
	},
	{
		id: 'connect-account',
		title: 'Connect account',
		href: '/onboarding?step=connect-account',
		status: 'upcoming',
		showActions: true,
		component: OnboardingConnectAccount
	},
	{
		id: 'general-settings',
		title: 'General settings',
		href: '/onboarding?step=general-settings',
		showActions: true,
		status: 'upcoming'
	}
] satisfies Step[];

export const onboardingSteps = writable<Step[]>(initialOnboardingSteps);

export const currentOnboardingStep = derived(onboardingSteps, ($onboardingSteps) => {
	return $onboardingSteps.find((step) => step.status === 'current');
});

export const navigateToOnboardingStep = (stepId: OnboardingStepId) => {
	let updatingStep: Step | undefined;

	onboardingSteps.update((steps) => {
		return steps.map((step) => {
			if (step.id === stepId) {
				updatingStep = step;

				return {
					...step,
					status: 'current'
				};
			}
			if (step.status === 'current') {
				return {
					...step,
					status: 'completed'
				};
			}
			return step;
		});
	});

	if (!updatingStep) {
		throw new Error(`Step with id ${stepId} not found`);
	}

	const { href } = updatingStep;

	return {
		href
	};
};

export const completeOnboardingStep = (stepId: OnboardingStepId) => {
	onboardingSteps.update((steps) => {
		return steps.map((step) => {
			if (step.id === stepId) {
				return {
					...step,
					status: 'completed'
				};
			}
			return step;
		});
	});
};

// Return to previous step
export const returnToPreviousStep = () => {
	let updatingStep: Step | undefined;

	onboardingSteps.update((steps) => {
		return steps.map((step) => {
			if (step.status === 'current') {
				updatingStep = step;

				return {
					...step,
					status: 'upcoming'
				};
			}
			if (step.status === 'completed') {
				return {
					...step,
					status: 'current'
				};
			}
			return step;
		});
	});

	if (!updatingStep) {
		throw new Error('No previous step found');
	}

	const { href } = updatingStep;

	return {
		href
	};
};
