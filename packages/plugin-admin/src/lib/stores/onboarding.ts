import OnboardingAccount from '$lib/components/onboarding/onboarding-account.svelte';
import OnboardingConnectAccount from '$lib/components/onboarding/onboarding-connect-account.svelte';
import OnboardingGeneralSettings from '$lib/components/onboarding/onboarding-general-settings.svelte';
import type { SvelteComponent } from 'svelte';
import { get, writable } from 'svelte/store';

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

const initialSteps: Step[] = [
	{
		id: 'welcome',
		title: 'Welcome',
		href: '/onboarding?step=welcome',
		status: 'completed',
		showActions: false,
		component: OnboardingAccount,
		initialising: false
	},
	{
		id: 'connect-account',
		title: 'Connect account',
		href: '/onboarding?step=connect-account',
		status: 'completed',
		showActions: true,
		component: OnboardingConnectAccount,
		initialising: false
	},
	{
		id: 'general-settings',
		title: 'General settings',
		href: '/onboarding?step=general-settings',
		status: 'current',
		showActions: true,
		component: OnboardingGeneralSettings,
		initialising: false
	}
];

export const onboardingSteps = writable<Step[]>(initialSteps);

const currentStepId = writable<OnboardingStepId>('welcome');

const setStepStatus = (stepId: OnboardingStepId, status: 'completed' | 'current' | 'upcoming') => {
	onboardingSteps.update((steps) => {
		return steps.map((step) => (step.id === stepId ? { ...step, status } : step));
	});
};

const findStepHref = (stepId: OnboardingStepId): string => {
	const step = initialSteps.find((step) => step.id === stepId);
	if (!step) {
		throw new Error('No href found for step: ' + stepId);
	}
	return step.href;
};

const goToStep = (stepId: OnboardingStepId) => {
	currentStepId.set(stepId);
	setStepStatus(stepId, 'current');
	return { href: findStepHref(stepId) };
};

const completeStep = (stepId: OnboardingStepId) => {
	setStepStatus(stepId, 'completed');
};

const completeAndNavigate = (toCOmpleteStepid: OnboardingStepId, nextStepId: OnboardingStepId) => {
	completeStep(toCOmpleteStepid);
	return goToStep(nextStepId);
};

const updateStepStatus = (
	steps: Step[],
	currentIndex: number,
	newStatus: 'completed' | 'upcoming'
) => {
	const newCurrentStep = steps[currentIndex + (newStatus === 'completed' ? 1 : -1)];
	steps[currentIndex].status = newStatus;
	newCurrentStep.status = 'current';
	currentStepId.set(newCurrentStep.id);
	return steps;
};

const previousStep = () => {
	onboardingSteps.update((steps) => {
		const currentIndex = steps.findIndex((step) => step.status === 'current');
		return currentIndex > 0 ? updateStepStatus(steps, currentIndex, 'upcoming') : steps;
	});
};

const nextStep = () => {
	onboardingSteps.update((steps) => {
		const currentIndex = steps.findIndex((step) => step.status === 'current');
		if (currentIndex < steps.length - 1) {
			steps = updateStepStatus(steps, currentIndex, 'completed');
		}
		return steps;
	});

	const href = findStepHref(get(currentStepId));

	if (!href) {
		throw new Error('No href found for current step');
	}

	return {
		href
	};
};

const setInitialising = (stepId: OnboardingStepId, initialising: boolean) => {
	onboardingSteps.update((steps) => {
		return steps.map((step) => (step.id === stepId ? { ...step, initialising } : step));
	});
};

export const useOnboarding = () => ({
	goToStep,
	completeStep,
	completeAndNavigate,
	previousStep,
	nextStep,
	setInitialising
});
