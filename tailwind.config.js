/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './**/*.php',
        './src/js/**/*.js',
    ],
    // Preflight is disabled: src/css/tailwind.css carries the festival normalize
    // and component layer, so Tailwind's reset should not fight it.
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
