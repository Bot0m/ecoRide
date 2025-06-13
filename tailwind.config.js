/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.html.twig',
        './assets/**/*.{js,ts}',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#6B8E23',
                secondary: '#FFF8E1',
                accent: '#FFB74D',
                background: '#FAFAFA',
                textPrimary: '#3E2723',
                textSecondary: '#6D4C41',

          // Variantes
                primaryLight: '#C3C64C',
                primaryDark: '#5E6018',
                secondaryLight: '#FFFBF0',
                secondaryDark: '#FFEAB6',
                accentLight: '#FFE0B2',
                accentDark: '#FFA726',
                backgroundLight: '#F9F9F9',
                backgroundDark: '#ECECEC',
                tSecondaryLight: '#8D6E63',
                tSecondaryDark: '#4E342E',
                tPrimaryLight: '#5D4037',
                tPrimaryDark: '#1B0C09',
            },
            fontFamily: {
                title: ['Raleway', 'sans-serif'],
                body: ['Quicksand', 'sans-serif'],
            },
            fontSize: {
                title: ['32px', { lineHeight: '40px', fontWeight: '700' }],
                subtitle: ['24px', { lineHeight: '32px', fontWeight: '500' }],
                base: ['16px', { lineHeight: '24px', fontWeight: '400' }],
                secondary: ['14px', { lineHeight: '20px', fontWeight: '400' }],
            },
        },
    },
    plugins: [],
};