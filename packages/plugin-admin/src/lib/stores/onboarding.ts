import type { SvelteComponent } from "svelte";
import { get, writable } from "svelte/store";
import OnboardingAccount from "$lib/components/onboarding/onboarding-account.svelte";
import OnboardingConnectAccount from "$lib/components/onboarding/onboarding-connect-account.svelte";
import OnboardingGeneralSettings from "$lib/components/onboarding/onboarding-general-settings.svelte";

export const OnboardingStepId = {
	welcome: "welcome",
	connectAccount: "connect-account",
	generalSettings: "general-settings",
} as const;

type OnboardingStepIdType = (typeof OnboardingStepId)[keyof typeof OnboardingStepId];

type Step = {
	id: OnboardingStepIdType;
	title: string;
	href: string;
	status: "completed" | "current" | "upcoming";
	showActions: boolean;

	// eslint-disable-next-line ts/no-explicit-any
	component?: typeof SvelteComponent<any>;
	initialising: boolean;
};

const initialSteps: Step[] = [
	{
		id: "welcome",
		title: "Welcome",
		href: "/onboarding?step=welcome",
		status: "current",
		showActions: false,
		component: OnboardingAccount,
		initialising: false,
	},
	{
		id: "connect-account",
		title: "Connect account",
		href: "/onboarding?step=connect-account",
		status: "upcoming",
		showActions: true,
		component: OnboardingConnectAccount,
		initialising: false,
	},
	{
		id: "general-settings",
		title: "General settings",
		href: "/onboarding?step=general-settings",
		status: "upcoming",
		showActions: true,
		component: OnboardingGeneralSettings,
		initialising: false,
	},
];

export const onboardingSteps = writable<Step[]>(initialSteps);

const currentStepId = writable<OnboardingStepIdType>("welcome");

function setStepStatus(stepId: OnboardingStepIdType, status: "completed" | "current" | "upcoming") {
	onboardingSteps.update((steps) => {
		return steps.map((step) => (step.id === stepId ? { ...step, status } : step));
	});
}

function findStepHref(stepId: OnboardingStepIdType): string {
	const step = initialSteps.find((step) => step.id === stepId);
	if (!step) {
		throw new Error(`No href found for step: ${stepId}`);
	}
	return step.href;
}

function goToStep(stepId: OnboardingStepIdType) {
	currentStepId.set(stepId);
	setStepStatus(stepId, "current");
	return { href: findStepHref(stepId) };
}

function completeStep(stepId: OnboardingStepIdType) {
	setStepStatus(stepId, "completed");
}

function completeAndNavigate(
	toCompleteStepId: OnboardingStepIdType,
	nextStepId: OnboardingStepIdType
) {
	completeStep(toCompleteStepId);
	return goToStep(nextStepId);
}

function updateStepStatus(
	steps: Step[],
	currentIndex: number,
	newStatus: "completed" | "upcoming"
) {
	const newCurrentStep = steps[currentIndex + (newStatus === "completed" ? 1 : -1)];
	steps[currentIndex].status = newStatus;
	newCurrentStep.status = "current";
	currentStepId.set(newCurrentStep.id);
	return steps;
}

function previousStep() {
	onboardingSteps.update((steps) => {
		const currentIndex = steps.findIndex((step) => step.status === "current");
		return currentIndex > 0 ? updateStepStatus(steps, currentIndex, "upcoming") : steps;
	});
}

function nextStep() {
	onboardingSteps.update((steps) => {
		const currentIndex = steps.findIndex((step) => step.status === "current");
		if (currentIndex < steps.length - 1) {
			steps = updateStepStatus(steps, currentIndex, "completed");
		}
		return steps;
	});

	const href = findStepHref(get(currentStepId));

	if (!href) {
		throw new Error("No href found for current step");
	}

	return {
		href,
	};
}

function setInitialising(stepId: OnboardingStepIdType, initialising: boolean) {
	onboardingSteps.update((steps) => {
		return steps.map((step) => (step.id === stepId ? { ...step, initialising } : step));
	});
}

function isLastStep() {
	const steps = get(onboardingSteps);
	const currentIndex = steps.findIndex((step) => step.status === "current");
	return currentIndex === steps.length - 1;
}

export function useOnboarding() {
	return {
		goToStep,
		completeStep,
		completeAndNavigate,
		previousStep,
		nextStep,
		setInitialising,
		isLastStep,
	};
}
