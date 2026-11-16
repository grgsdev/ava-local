import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme'; // Usaremos este
/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts,vue}',
    ],

    theme: {
    	extend: {
    		fontFamily: {
    			sans: [
    				'var(--font-sans)',
                    ...defaultTheme.fontFamily.sans
                ],
    			heading: [
    				'var(--font-heading)',
                    ...defaultTheme.fontFamily.sans
                ]
    		},
    		borderRadius: {
    			lg: 'var(--radius)',
    			md: 'calc(var(--radius) - 2px)',
    			sm: 'calc(var(--radius) - 4px)'
    		},
    		colors: {
    			border: 'hsl(var(--border))',
    			input: 'hsl(var(--input))',
    			ring: 'hsl(var(--ring))',
    			background: 'hsl(var(--background))',
    			foreground: 'hsl(var(--foreground))',
    			card: {
    				DEFAULT: 'hsl(var(--card))',
    				foreground: 'hsl(var(--card-foreground))'
    			},
    			popover: {
    				DEFAULT: 'hsl(var(--popover))',
    				foreground: 'hsl(var(--popover-foreground))'
    			},
    			primary: {
    				DEFAULT: 'hsl(var(--primary))',
    				foreground: 'hsl(var(--primary-foreground))'
    			},
    			secondary: {
    				DEFAULT: 'hsl(var(--secondary))',
    				foreground: 'hsl(var(--secondary-foreground))'
    			},
    			muted: {
    				DEFAULT: 'hsl(var(--muted))',
    				foreground: 'hsl(var(--muted-foreground))'
    			},
    			accent: {
    				DEFAULT: 'hsl(var(--accent))',
    				foreground: 'hsl(var(--accent-foreground))'
    			},
    			destructive: {
    				DEFAULT: 'hsl(var(--destructive))',
    				foreground: 'hsl(var(--destructive-foreground))'
    			},
    			chart: {
    				'1': 'hsl(var(--chart-1))',
    				'2': 'hsl(var(--chart-2))',
    				'3': 'hsl(var(--chart-3))',
    				'4': 'hsl(var(--chart-4))',
    				'5': 'hsl(var(--chart-5))'
    			},
    			'accent-soft': {
    				DEFAULT: 'hsl(var(--accent-soft))',
    				foreground: 'hsl(var(--accent-soft-foreground))'
    			}
    		},
    		borderColor: {
    			DEFAULT: 'hsl(var(--border))'
    		},
    		backgroundImage: {
    			'gradient-primary': 'linear-gradient(to right top, hsl(var(--primary)), hsl(var(--accent)))',
    			'gradient-primary-soft': 'linear-gradient(to right top, hsl(var(--primary) / 0.8), hsl(var(--accent) / 0.8))',
    			'gradient-radial': 'radial-gradient(ellipse at center, var(--tw-gradient-stops))'
    		},
    		keyframes: {
    			'accordion-down': {
    				from: {
    					height: '0'
    				},
    				to: {
    					height: 'var(--reka-accordion-content-height)'
    				}
    			},
    			'accordion-up': {
    				from: {
    					height: 'var(--reka-accordion-content-height)'
    				},
    				to: {
    					height: '0'
    				}
    			}
    		},
    		animation: {
    			'accordion-down': 'accordion-down 0.2s ease-out',
    			'accordion-up': 'accordion-up 0.2s ease-out'
    		}
    	}
    },

    plugins: [
        forms,
        require('tailwindcss-animate'),
        require('@tailwindcss/typography')({
            // Adicionamos uma nova configuração de CSS para sobrescrever os padrões
            css: {
                // Para todos os elementos <pre> dentro de um .prose...
                pre: {
                    // ... definimos a margem inferior como 0.
                    marginBottom: '0',
                },
                // E para garantir o espaçamento, dizemos que qualquer elemento
                // que venha diretamente a seguir a um <pre> deve ter uma margem de topo.
                'pre + *': {
                    marginTop: '1.5rem',
                },
            },
        }),
    ],
};
