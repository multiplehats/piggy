import OnboardingAccount from '$lib/components/onboarding/onboarding-account.svelte';
import OnboardingConnectAccount from '$lib/components/onboarding/onboarding-connect-account.svelte';
import type { SvelteComponent } from 'svelte';
import { writable } from 'svelte/store';

export const OnboardingStepId = {
	welcome: 'welcome',
	connectAccount: 'connect-account',
	generalSettings: 'general-settings'
} as const;

type OnboardingStepId = (typeof OnboardingStepId)[keyof typeof OnboardingStepId];

interface Step {
	id: OnboardingStepId;
	title: string;
	href: string;
	status: 'completed' | 'current' | 'upcoming';
	showActions: boolean;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	component?: typeof SvelteComponent<any>;
	initialising: boolean;
}

export const onboardingSteps = writable<Step[]>([
	{
		id: 'welcome',
		title: 'Welcome',
		href: '/onboarding?step=welcome',
		status: 'current',
		showActions: false,
		component: OnboardingAccount,
		initialising: false
	},
	{
		id: 'connect-account',
		title: 'Connect account',
		href: '/onboarding?step=connect-account',
		status: 'upcoming',
		showActions: true,
		component: OnboardingConnectAccount,
		initialising: false
	},
	{
		id: 'general-settings',
		title: 'General settings',
		href: '/onboarding?step=general-settings',
		showActions: true,
		status: 'upcoming',
		initialising: false
	}
]);

export const useOnboarding = () => {
	return {
		goToStep: (stepId: OnboardingStepId) => {
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
		},
		completeStep: (stepId: OnboardingStepId) => {
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
		},
		previousStep: () => {
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
		},
		nextStep: () => {
			let updatingStep: Step | undefined;

			onboardingSteps.update((steps) => {
				return steps.map((step) => {
					if (step.status === 'current') {
						updatingStep = step;

						return {
							...step,
							status: 'completed'
						};
					}
					if (step.status === 'upcoming') {
						return {
							...step,
							status: 'current'
						};
					}
					return step;
				});
			});

			if (!updatingStep) {
				throw new Error('No next step found');
			}

			const { href } = updatingStep;

			return {
				href
			};
		},
		setInitialising: (stepId: OnboardingStepId, initialising: boolean) => {
			onboardingSteps.update((steps) => {
				return steps.map((step) => {
					if (step.id === stepId) {
						return {
							...step,
							initialising
						};
					}
					return step;
				});
			});
		}
	};
};
