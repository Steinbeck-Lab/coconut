import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    dark: '#451300',
                    light: '#92400e'
                },
                secondary: {
                    dark: '#32994d',
                    light: '#32994d'
                },
                text: {
                    dark: '#374151',
                    light: '#6b7280'
                },
                link: {
                    dark: '#4f46e5',
                    light: '#818cf8'
                },
                danger: {
                    dark: '#dc2626',
                    light: '#f87171'
                }
            },
        },
    },

    plugins: [forms, typography],
};
