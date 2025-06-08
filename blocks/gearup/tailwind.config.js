const plugin = require('tailwindcss/plugin');

module.exports = {
    prefix: 'gu-',
    important: '.block_gearup',
    content: [
        './templates/**/*.mustache',
        './amd/src/**/*.js',
        './ts/src/**/*.tsx',
        './classes/output/**/*.php',
        './classes/table/**/*.php',
        './classes/form/**/*.php',
        './classes/local/block/**/*.php',
        './classes/local/assigner/**/*.php',
        './classes/local/controller/**/*.php',
        './classes/local/shortcodes/**/*.php',
    ],
    theme: {
        extend: {
            maxWidth: (theme) => theme('space'),
            minWidth: (theme) => theme('space'),
            fontSize: {
                '2xs': ['0.6875rem', '1'],
                '3xs': ['0.6875rem', '1']
            },
            gridTemplateColumns: {
                '1fr': 'repeat(auto-fit, minmax(0, 1fr))',
            },
            transitionDelay: {
                '400': '400ms'
            },
            animation: {
                'spinhero': 'spinhero 1000ms ease-out',
                'stress': 'stress 150ms 0s 5 linear',
                'typing': 'typing 1500ms linear infinite',
            },
            keyframes: {
                spinhero: {
                    '0%': {transform: 'rotate(0)'},
                    '100%': {transform: 'rotate(720deg)'},
                },
                stress: {
                    '0%': {transform: 'rotate(0)'},
                    '25%': {transform: 'rotate(5deg)'},
                    '50%': {transform: 'rotate(0)'},
                    '75%': {transform: 'rotate(-5deg)'},
                    '100%': {transform: 'rotate(0)'},
                },
                typing: {
                    '0%': {transform: 'translateY(0)', opacity: '0.5'},
                    '8%': {transform: 'translateY(-4px)', opacity: '1'},
                    '16%': {transform: 'translateY(0)'},
                    '100%': {transform: 'translateY(0)', opacity: '0.5'},
                }
            }
        },
    },
    corePlugins: {
        preflight: false,
        divideWidth: false
    },
    plugins: [
        // Create an animation-delay utility.
        plugin(({matchUtilities, theme}) => {
            matchUtilities(
                {
                    "animation-delay": (value) => {
                        return {
                            "animation-delay": value,
                        };
                    },
                },
                {
                    values: theme("transitionDelay"),
                }
            );
        }),
        // Redefine the 'space' and 'divideWidth' plugin because Moodle 3.11 (and older most likely)
        // do not properly parse its generated CSS (calc + var). This disables the utilities:
        // `divide-[x/y]-reverse`, `space-[x/y]-reverse`.
        plugin(function({matchUtilities, theme}) {
            matchUtilities({
                'divide-x': value => {
                    value = value === '0' ? '0px' : value;
                    return {
                        '& > :not([hidden]) ~ :not([hidden])': {
                            '@defaults border-width': {},
                            'border-left-width': `${value}`,
                            'border-right-width': `0`
                        }
                    };
                },
                'divide-y': value => {
                    value = value === '0' ? '0px' : value;
                    return {
                        '& > :not([hidden]) ~ :not([hidden])': {
                            '@defaults border-width': {},
                            'border-top-width': `${value}`,
                            'border-bottom-width': `0`
                        }
                    };
                }
            }, {
                values: theme('divideWidth'),
                type: ['line-width', 'length', 'any']
            });
        }),
        plugin(function({matchUtilities, theme, variants}) {
            matchUtilities({
                'space-x': value => {
                    value = value === '0' ? '0px' : value;
                    return {
                        '& > :not([hidden]) ~ :not([hidden])': {
                            'margin-right': `0`,
                            'margin-left': `${value}`
                        }
                    };
                },
                'space-y': value => {
                    value = value === '0' ? '0px' : value;
                    return {
                        '& > :not([hidden]) ~ :not([hidden])': {
                            'margin-top': `${value}`,
                            'margin-bottom': `0`
                        }
                    };
                }
            }, {
                values: theme('space'),
                variants: variants('space'),
                type: 'any'
            });
        })
    ],
};
