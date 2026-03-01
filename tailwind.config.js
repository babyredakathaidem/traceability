import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                cosmic: {
                    950: '#07060a',
                    900: '#0b0a12',
                    800: '#121022',
                    700: '#1a1733',
                },
                brand: {
                    50: '#fff3e9',
                    100: '#ffe1c8',
                    200: '#ffc18a',
                    300: '#ff9f54',
                    400: '#ff842b',
                    500: '#ff7a18',
                    600: '#ea5f08',
                    700: '#c95700',
                },
            },

            backgroundImage: {
                cosmic:
                    "radial-gradient(1000px 600px at 15% 20%, rgba(255,122,24,.18), transparent 55%), radial-gradient(900px 600px at 85% 10%, rgba(255,154,61,.10), transparent 60%), linear-gradient(180deg, #07060a, #0b0a12)",
            },

            borderColor: {
                glass: 'rgba(255,255,255,.10)',
            },

            boxShadow: {
                'glow-orange':
                    '0 0 0 1px rgba(255,122,24,.25), 0 12px 40px rgba(0,0,0,.55)',
            },
        },
    },

    plugins: [forms],
};