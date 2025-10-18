const defaultTheme = require('tailwindcss/defaultTheme');

const withOpacity = (variable) => ({ opacityValue }) => {
    if (opacityValue === undefined) {
        return `rgb(var(${variable}))`;
    }

    return `rgb(var(${variable}) / ${opacityValue})`;
};

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './assets/**/*.{js,jsx,ts,tsx}',
        './templates/**/*.{html,twig}',
        './src/**/*.{php,twig}',
        './docs/ui-guidelines/**/*.{md,mdx}',
    ],
    theme: {
        extend: {
            colors: {
                surface: {
                    body: withOpacity('--surface-body'),
                    elevated: withOpacity('--surface-elevated'),
                    sunken: withOpacity('--surface-sunken'),
                },
                border: {
                    DEFAULT: withOpacity('--border-default'),
                    subtle: withOpacity('--border-subtle'),
                },
                text: {
                    primary: withOpacity('--text-primary'),
                    secondary: withOpacity('--text-secondary'),
                    tertiary: withOpacity('--text-tertiary'),
                },
                accent: {
                    primary: withOpacity('--accent-primary'),
                    secondary: withOpacity('--accent-secondary'),
                },
                state: {
                    success: withOpacity('--state-success'),
                    warning: withOpacity('--state-warning'),
                    error: withOpacity('--state-error'),
                },
                overlay: withOpacity('--overlay'),
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            maxWidth: {
                workspace: '80rem',
                conversation: '56rem',
            },
            spacing: {
                '2.5': '0.625rem',
                '3.5': '0.875rem',
                '5.5': '1.375rem',
            },
            boxShadow: {
                accent: '0 8px 24px -18px rgba(58, 209, 194, 0.8)',
                panel: '0 12px 40px -20px rgba(0, 0, 0, 0.6)',
                card: '0 16px 48px -28px rgba(0, 0, 0, 0.75)',
                inner: 'inset 0 1px 0 0 rgba(255, 255, 255, 0.02)',
            },
            borderRadius: {
                xl: '0.9rem',
                '2xl': '1.25rem',
            },
            transitionDuration: {
                150: '150ms',
                200: '200ms',
                250: '250ms',
            },
            backdropBlur: {
                xs: '2px',
            },
            screens: {
                mobile: { max: '640px' },
                tablet: { min: '641px', max: '1024px' },
                desktop: '1025px',
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};

