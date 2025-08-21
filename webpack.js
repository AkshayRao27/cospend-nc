const path = require('path')
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

// Adjust performance limits for complex app with cross-project features
// The default webpack limits (244KB) are too restrictive for a feature-rich application
// like Cospend which includes charts, cross-project aggregation, and multiple heavy components
webpackConfig.performance = {
	maxAssetSize: 2000000, // 2MB instead of 244KB default - allows for larger feature chunks
	maxEntrypointSize: 5000000, // 5MB instead of 244KB default - accommodates full app bundle
	hints: 'warning', // Still show warnings but less aggressive - helps with build pipeline
}

// Add tree shaking and module concatenation optimizations
// These optimizations are critical for the cross-project feature performance
webpackConfig.resolve = {
	...webpackConfig.resolve,
	// Help webpack find modules faster - important for development builds
	// with the new lazy-loaded cross-project components
	symlinks: false,
}

// Enable module concatenation (scope hoisting) for smaller bundles
// This reduces the overhead of webpack's module system by inlining smaller modules
// Particularly beneficial for the utility functions (debounce, memoize) added for cross-project features
if (!isDev) {
	webpackConfig.optimization.concatenateModules = true
}

const appId = 'cospend'
webpackConfig.entry = {
	main: { import: path.join(__dirname, 'src', 'main.js'), filename: appId + '-main.js' },
	sharePassword: { import: path.join(__dirname, 'src', 'sharePassword.js'), filename: appId + '-sharePassword.js' },
	dashboard: { import: path.join(__dirname, 'src', 'dashboard.js'), filename: appId + '-dashboard.js' },
	adminSettings: { import: path.join(__dirname, 'src', 'adminSettings.js'), filename: appId + '-adminSettings.js' },
}

// Enable code splitting and chunk optimization
// For cross-project features which can be large and are not always needed
// This strategy ensures the main bundle stays small while allowing heavy features to load on demand
webpackConfig.optimization = {
	...webpackConfig.optimization,
	splitChunks: {
		chunks: 'all',
		minSize: 20000, // Minimum size for creating a chunk (20KB)
		maxSize: 1500000, // 1.5MB max chunk size - prevents overly large chunks
		cacheGroups: {
			default: {
				minChunks: 2, // Must be used by at least 2 chunks to be split
				priority: -20,
				reuseExistingChunk: true,
			},
			vendors: {
				test: /[\\/]node_modules[\\/]/,
				name: 'vendors',
				priority: -10,
				reuseExistingChunk: true,
			},
			// Split large vendor libraries into separate chunks for better caching
			// This is especially important for the chart.js dependency used in statistics
			nextcloud: {
				test: /[\\/]node_modules[\\/]@nextcloud[\\/]/,
				name: 'nextcloud-vendors',
				priority: 10,
				reuseExistingChunk: true,
			},
			vue: {
				test: /[\\/]node_modules[\\/](vue|vuex)[\\/]/,
				name: 'vue-vendors',
				priority: 20, // Highest priority - Vue is core to the application
				reuseExistingChunk: true,
			},
		},
	},
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

module.exports = webpackConfig
