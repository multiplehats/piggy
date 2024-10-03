import { round } from "./math";

type RgbColor = {
	r: number;
	g: number;
	b: number;
};

type RgbaColor = {
	a: number;
} & RgbColor;

type HsvColor = {
	h: number;
	s: number;
	v: number;
};

type HsvaColor = {
	a: number;
} & HsvColor;

type HslaColor = {
	h: number;
	s: number;
	l: number;
	a: number;
};

export const isHex = (color: string): boolean => /^#([A-F0-9]{6}|[A-F0-9]{3})$/i.test(color);

export function hexToRgba(hex: string): RgbaColor {
	if (isHex(hex)) hex = hex.substring(1);

	if (hex.length === 3 || hex.length === 4) {
		return {
			r: Number.parseInt(hex[0] + hex[0], 16),
			g: Number.parseInt(hex[1] + hex[1], 16),
			b: Number.parseInt(hex[2] + hex[2], 16),
			a: hex.length === 4 ? round(Number.parseInt(hex[3] + hex[3], 16) / 255, 2) : 1,
		};
	}

	return {
		r: Number.parseInt(hex.substring(0, 2), 16),
		g: Number.parseInt(hex.substring(2, 4), 16),
		b: Number.parseInt(hex.substring(4, 6), 16),
		a: hex.length === 8 ? round(Number.parseInt(hex.substring(6, 8), 16) / 255, 2) : 1,
	};
}

export function hexToRgbaString(hex: string): string {
	const { r, g, b, a } = hexToRgba(hex);
	return `rgba(${r}, ${g}, ${b}, ${a})`;
}

export function isHsva(color: string): boolean {
	return /^hsva?\(\d{1,3},\s?\d{1,3}%,\s?\d{1,3}%,\s?0?\.\d+\)$/.test(color);
}

export function parseHsva(hsva: string): HsvaColor {
	const match = hsva.match(/^hsva?\((\d{1,3}),\s?(\d{1,3})%,\s?(\d{1,3})%,\s?(0?\.\d+)\)$/);
	if (match) {
		return {
			h: Number.parseInt(match[1]),
			s: Number.parseInt(match[2]),
			v: Number.parseInt(match[3]),
			a: Number.parseFloat(match[4]),
		};
	}

	return { h: 0, s: 0, v: 0, a: 1 };
}

export function hsvaToRgba({ h, s, v, a }: HsvaColor): RgbaColor {
	h = (h / 360) * 6;
	s /= 100;
	v /= 100;

	const hh = Math.floor(h);
	const b = v * (1 - s);
	const c = v * (1 - (h - hh) * s);
	const d = v * (1 - (1 - h + hh) * s);
	const module = hh % 6;

	return {
		r: round([v, c, b, b, d, v][module] * 255),
		g: round([d, v, v, c, b, b][module] * 255),
		b: round([b, b, d, v, v, c][module] * 255),
		a: round(a, 2),
	};
}

export function hsvaToRgbaString(hsva: string): string {
	const { r, g, b, a } = hsvaToRgba(parseHsva(hsva));
	return `rgba(${r}, ${g}, ${b}, ${a})`;
}

export function isHsla(color: string): boolean {
	return /^hsla?\(\d{1,3},\s?\d{1,3}%,\s?\d{1,3}%,\s?0?\.\d+\)$/.test(color);
}

export function hslaToRgba({ h, s, l, a }: HslaColor): RgbaColor {
	s /= 100;
	l /= 100;

	const c = (1 - Math.abs(2 * l - 1)) * s;
	const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
	const m = l - c / 2;

	let r = 0;
	let g = 0;
	let b = 0;

	if (h >= 0 && h < 60) {
		r = c;
		g = x;
		b = 0;
	} else if (h >= 60 && h < 120) {
		r = x;
		g = c;
		b = 0;
	} else if (h >= 120 && h < 180) {
		r = 0;
		g = c;
		b = x;
	} else if (h >= 180 && h < 240) {
		r = 0;
		g = x;
		b = c;
	} else if (h >= 240 && h < 300) {
		r = x;
		g = 0;
		b = c;
	} else if (h >= 300 && h < 360) {
		r = c;
		g = 0;
		b = x;
	}

	r = round((r + m) * 255);
	g = round((g + m) * 255);
	b = round((b + m) * 255);

	return { r, g, b, a };
}

export function hslaToRgbaString(hsla: string): string {
	const match = hsla.match(/^hsla?\((\d{1,3}),\s?(\d{1,3})%,\s?(\d{1,3})%,\s?(0?\.\d+)\)$/);
	if (match) {
		const h = Number.parseInt(match[1]);
		const s = Number.parseInt(match[2]);
		const l = Number.parseInt(match[3]);
		const a = Number.parseFloat(match[4]);

		const rgba = hslaToRgba({ h, s, l, a });
		return `rgba(${rgba.r}, ${rgba.g}, ${rgba.b}, ${rgba.a})`;
	}
	throw new Error("Invalid HSLa color string");
}

export function colorToRgbaString(color: string): string {
	if (isHex(color)) return hexToRgbaString(color);
	if (isHsva(color)) return hsvaToRgbaString(color);
	if (isHsla(color)) return hslaToRgbaString(color);

	return color;
}
