import { fontFamily } from "tailwindcss/defaultTheme";

// .bg-primary-100{
//   background-color: #E0E2FF;
// }.bg-primary-200{
//   background-color: #C7C9FF;
// }.bg-primary-25{
//   background-color: #F6F7FE;
// }.bg-primary-400{
//   background-color: #9693FD;
// }.bg-primary-50{
//   background-color: #EEEFFF;
// }.bg-primary-50\/50{
//   background-color: rgb(238 239 255 / 0.5);
// }.bg-primary-500{
//   background-color: #6360FF;
// }.bg-primary-600{
//   background-color: #3430FF;
// }.bg-primary-700{
//   background-color: #221FA6;
// }.bg-primary-900{
//   background-color: #100F4C;
// }
const primaryColors = {
	50: "#EEEFFF",
	100: "#E0E2FF",
	200: "#C7C9FF",
	400: "#9693FD",
	500: "#6360FF",
	600: "#3430FF",
	700: "#221FA6",
	900: "#100F4C",
};

/** @type {import('tailwindcss').Config} */
const config = {
	darkMode: ["class"],
	content: ["./src/**/*.{html,js,svelte,ts}"],
	theme: {
		container: {
			center: true,
			padding: "2rem",
			screens: {
				"2xl": "1400px",
			},
		},
		extend: {
			colors: {
				// Custom colors.
				piggy: {
					orange: "#fc5200",
					"orange-hover": "#d44500",
					primary: { ...primaryColors },
				},

				// Shadcn
				border: "hsl(var(--border) / <alpha-value>)",
				input: "hsl(var(--input) / <alpha-value>)",
				ring: "hsl(var(--ring) / <alpha-value>)",
				background: "hsl(var(--background) / <alpha-value>)",
				foreground: "hsl(var(--foreground) / <alpha-value>)",
				primary: {
					DEFAULT: "hsl(var(--primary) / <alpha-value>)",
					foreground: "hsl(var(--primary-foreground) / <alpha-value>)",
				},
				secondary: {
					DEFAULT: "hsl(var(--secondary) / <alpha-value>)",
					foreground: "hsl(var(--secondary-foreground) / <alpha-value>)",
				},
				destructive: {
					DEFAULT: "hsl(var(--destructive) / <alpha-value>)",
					foreground: "hsl(var(--destructive-foreground) / <alpha-value>)",
				},
				muted: {
					DEFAULT: "hsl(var(--muted) / <alpha-value>)",
					foreground: "hsl(var(--muted-foreground) / <alpha-value>)",
				},
				accent: {
					DEFAULT: "hsl(var(--accent) / <alpha-value>)",
					foreground: "hsl(var(--accent-foreground) / <alpha-value>)",
				},
				popover: {
					DEFAULT: "hsl(var(--popover) / <alpha-value>)",
					foreground: "hsl(var(--popover-foreground) / <alpha-value>)",
				},
				card: {
					DEFAULT: "hsl(var(--card) / <alpha-value>)",
					foreground: "hsl(var(--card-foreground) / <alpha-value>)",
				},
			},
			borderRadius: {
				lg: "var(--radius)",
				md: "calc(var(--radius) - 2px)",
				sm: "calc(var(--radius) - 4px)",
			},
			fontFamily: {
				sans: ["Inter", ...fontFamily.sans],
			},
		},
	},
};
export default config;
