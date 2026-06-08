import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './config/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Brand font — Old English Text MT (Windows system font, no file required)
                brand: ['"Old English Text MT"', '"Palatino Linotype"', 'Palatino', 'serif'],
            },
            animation: {
                'cta-pulse': 'cta-pulse 2.4s ease-in-out infinite',
            },
            keyframes: {
                'cta-pulse': {
                    '0%, 100%': { transform: 'scale(1)', boxShadow: '0 0 0 0 rgba(251, 191, 36, 0.35)' },
                    '50%':      { transform: 'scale(1.045)', boxShadow: '0 0 0 10px rgba(251, 191, 36, 0)' },
                },
            },
            colors: {
                // Primary — forest green (SPUP SITE department color)
                primary: {
                    50:  '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                },
                // Accent — warm academic gold
                accent: {
                    50:  '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                },
                // Status — semantic surface tints (use as bg/text pairs, never raw Tailwind colors)
                // bg-status-*      → badge background tint
                // text-status-*-fg → badge foreground text on light tint
                'status-pending':  { DEFAULT: '#c7d2fe', fg: '#3730a3' }, // indigo-200 bg / indigo-800 text
                'status-revision': { DEFAULT: '#fde68a', fg: '#92400e' }, // amber-200  bg / amber-800  text
                'status-approved': { DEFAULT: '#a7f3d0', fg: '#065f46' }, // emerald-200 bg / emerald-800 text
            },
        },
    },

    plugins: [forms],
};
