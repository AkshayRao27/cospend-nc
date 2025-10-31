const path = require('path')
const webpack = require('webpack')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const ESLintPlugin = require('eslint-webpack-plugin')
const StyleLintPlugin = require('stylelint-webpack-plugin')
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
const shouldAnalyze = process.argv.includes('--analyze')
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'
// webpackConfig.bail = false

webpackConfig.stats = {
	colors: true,
	modules: false,
}

const appId = 'cospend'
webpackConfig.entry = {
	main: { import: path.join(__dirname, 'src', 'main.js'), filename: appId + '-main.js' },
	sharePassword: { import: path.join(__dirname, 'src', 'sharePassword.js'), filename: appId + '-sharePassword.js' },
	dashboard: { import: path.join(__dirname, 'src', 'dashboard.js'), filename: appId + '-dashboard.js' },
	adminSettings: { import: path.join(__dirname, 'src', 'adminSettings.js'), filename: appId + '-adminSettings.js' },
}

webpackConfig.plugins.push(
	new ESLintPlugin({
		extensions: ['js', 'vue'],
		files: 'src',
		failOnError: !isDev,
	}),
)
webpackConfig.plugins.push(
	new StyleLintPlugin({
		files: 'src/**/*.{css,scss,vue}',
		failOnError: !isDev,
	}),
)

// Add bundle analyzer when --analyze flag is used
// This tool helps developers understand bundle composition and optimize performance
// Usage: npm run build:analyze
// Particularly useful for analyzing the impact of cross-project features on bundle size
if (shouldAnalyze) {
	webpackConfig.plugins.push(
		new BundleAnalyzerPlugin({
			analyzerMode: 'server',
			openAnalyzer: true, // Automatically opens browser with analysis
		}),
	)
}

// Add ProvidePlugin to inject process and Buffer globals
webpackConfig.plugins.push(
	new webpack.ProvidePlugin({
		process: 'process/browser.js',
		Buffer: ['buffer', 'Buffer'],
	}),
)

// Fix resolve.extensions to have leading dots
webpackConfig.resolve.extensions = ['.js', '.ts', '.vue', '.json']

// Fix resolve.fallback for node polyfills
webpackConfig.resolve.fallback = webpackConfig.resolve.fallback || {}
webpackConfig.resolve.fallback.process = require.resolve('process/browser.js')
webpackConfig.resolve.fallback.buffer = require.resolve('buffer/')

// Ensure fully specified imports work
webpackConfig.resolve.fullySpecified = false

module.exports = webpackConfig
