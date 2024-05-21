import OnboardingAccount from '$lib/components/onboarding/onboarding-account.svelte';
import OnboardingConnectAccount from '$lib/components/onboarding/onboarding-connect-account.svelte';
import type { SvelteComponent } from 'svelte';
import { derived, writable } from 'svelte/store';

export interface Step {
	id: string;
	title: string;
	href: string;
	status: 'completed' | 'current' | 'upcoming';
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	component?: typeof SvelteComponent<any>;
}

export const initialOnboardingSteps = [
	{
		id: 'welcome',
		title: 'Welcome',
		href: '/onboarding?step=welcome',
		status: 'current',
		component: OnboardingAccount
	},
	{
		id: 'connect-account',
		title: 'Connect account',
		href: '/onboarding?step=connect-account',
		status: 'upcoming',
		component: OnboardingConnectAccount
	},
	{
		id: 'general-settings',
		title: 'General settings',
		href: '/onboarding?step=general-settings',
		status: 'upcoming'
	}
] satisfies Step[];

export const onboardingSteps = writable<Step[]>(initialOnboardingSteps);

export const currentOnboardingStep = derived(onboardingSteps, ($onboardingSteps) => {
	return $onboardingSteps.find((step) => step.status === 'current');
});

export const navigateToOnboardingStep = (stepId: string) => {
	onboardingSteps.update((steps) => {
		return steps.map((step) => {
			if (step.id === stepId) {
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
};
