const path = require("node:path");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const WooCommerceDependencyExtractionWebpackPlugin = require("@woocommerce/dependency-extraction-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

// Remove SASS rule from the default config so we can define our own.
const defaultRules = defaultConfig.module.rules.filter((rule) => {
	return String(rule.test) !== String(/\.(sc|sa)ss$/);
});

const entries = {
	"gift-card-recipient": "./src/gift-card-recipient/index.tsx",
	// Add other blocks as needed
};

module.exports = {
	...defaultConfig,
	entry: entries,
	output: {
		path: path.resolve(__dirname, "src"),
		filename: "[name]/build/index.js",
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultRules,
			{
				test: /\.(sc|sa)ss$/,
				exclude: /node_modules/,
				use: [
					MiniCssExtractPlugin.loader,
					{ loader: "css-loader", options: { importLoaders: 1 } },
					{
						loader: "sass-loader",
						options: {
							sassOptions: {
								includePaths: ["src/css"],
							},
							additionalData: (content, loaderContext) => {
								const { resourcePath, rootContext } = loaderContext;
								const relativePath = path.relative(rootContext, resourcePath);

								if (relativePath.startsWith("src/css/")) {
									return content;
								}

								// Add code here to prepend to all .scss/.sass files.
								// return `@import "_colors"; ${content}`;
								return content;
							},
						},
					},
				],
			},
		],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) => plugin.constructor.name !== "DependencyExtractionWebpackPlugin"
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
		new MiniCssExtractPlugin({
			filename: `[name]/build/[name].css`,
		}),
	],
};
