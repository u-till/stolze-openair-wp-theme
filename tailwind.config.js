/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './**/*.php',
        './src/js/**/*.js',
    ],
    // Preflight is disabled: the ported SCSS ships its own normalize + design
    // system, and we don't want Tailwind's reset fighting it. Utilities remain
    // available for layout/chrome.
    corePlugins: {
        preflight: false,
    },
    theme: {
        extend: {
            colors: {
                'so-bg': '#ffffef',
                'so-text': 'rgba(0,0,0,0.95)',
                'so-primary': '#6a9bea',
            },
            fontFamily: {
                sans: ['neue-haas-grotesk-display', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
