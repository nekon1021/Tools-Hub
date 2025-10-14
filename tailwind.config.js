// tailwind.config.js
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

/** @type {import('tailwindcss').Config} */
export default {
  // ← もし <script> 内でクラスを生成するJSがあるなら js/ts も含める
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.ts',
  ],

  // ここが重要：Bladeで動的生成されるテーマ/自作ユーティリティをパージ対象外に
  safelist: [
    'tool-nav', 'text-var', 'border-var', 'hover-bg', 'border-y-var',
    'theme-light', 'theme-dark', 'theme-sepia',
    // もし将来テーマが増えるならパターンも併用（乱用は避ける）
    { pattern: /theme-(.+)/ },
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [forms],
}
